<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DoorLog;
use App\Models\RfidCard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DoorApiController extends Controller
{
    private const VERIFICATION_TIMEOUT = 30;

    /**
     * Dipanggil oleh ESP32 saat ada kartu RFID yang di-tap.
     * Endpoint: POST /api/door/check
     */
    public function check(Request $request): JsonResponse
    {
        $rawUid = $request->input('uid');

        if (! $rawUid) {
            return response()->json([
                'granted' => false,
                'message' => 'UID tidak boleh kosong',
            ], 400);
        }

        // Format UID: Bersihkan karakter aneh dan jadikan huruf kapital (contoh: "82 3E 19 B0" atau "823E19B0")
        $cleanUid = strtoupper(trim($rawUid));
        $hexOnly = preg_replace('/[^0-9A-F]/', '', $cleanUid);

        // Capture only the first card tapped while the web form is waiting.
        if (Cache::get('rfid_capture_active')) {
            Cache::add('pending_rfid_tap', [
                'uid' => $hexOnly,
            ], 35);
        }

        Log::info("ESP32 CHECK: rawUid=[{$rawUid}], cleanUid=[{$cleanUid}], hexOnly=[{$hexOnly}]");
        Log::info("CURRENT CARDS IN DB: " . RfidCard::all()->pluck('uid', 'name')->toJson());

        // 2. CEK APAKAH KARTU TERDAFTAR DI DATABASE (Fleksibel dengan atau tanpa spasi)
        $card = RfidCard::where('uid', $cleanUid)
            ->orWhere('uid', $hexOnly)
            ->orWhereRaw("REPLACE(uid, ' ', '') = ?", [$hexOnly])
            ->first();

        // Fallback pencarian langsung ke koleksi PHP jika ada perbedaan format spasi
        if (! $card) {
            $card = RfidCard::all()->first(function ($c) use ($hexOnly) {
                return strtoupper(preg_replace('/[^0-9A-F]/', '', $c->uid)) === $hexOnly;
            });
        }

        Log::info("ESP32 CARD MATCH: " . ($card ? "FOUND [{$card->name}, role={$card->role}, active={$card->active}]" : "NOT FOUND"));

        $validated = $request->validate([
            'door' => ['nullable', 'in:gudang'],
            'direction' => ['nullable', 'in:in,out'],
        ]);
        $door = $validated['door'] ?? 'gudang';
        $direction = $validated['direction'] ?? 'in';

        if ($card && ! $card->active) {
            DoorLog::create([
                'uid' => $card->uid,
                'name' => $card->name,
                'role' => $card->role,
                'door' => $door,
                'direction' => $direction,
                'method' => 'rfid',
                'result' => 'denied',
                'reason' => 'Kartu diblokir',
                'at' => now(),
            ]);

            return response()->json([
                'granted' => false,
                'name' => $card->name,
                'role' => strtoupper($card->role),
                'message' => 'Kartu diblokir',
            ]);
        }

        if ($card && $card->active) {
            $role = strtolower(trim($card->role));
            $pending = $this->activeVerification($door);

            if ($role === 'teknisi') {
                if (! $pending) {
                    $card->update(['last_used_at' => now()]);
                    $log = DoorLog::create([
                        'uid' => $card->uid,
                        'name' => $card->name,
                        'role' => $card->role,
                        'door' => $door,
                        'direction' => $direction,
                        'method' => 'two_step',
                        'result' => 'pending',
                        'reason' => 'Menunggu tap kartu Admin',
                        'at' => now(),
                    ]);

                    Cache::put($this->verificationKey($door), [
                        'log_id' => $log->id,
                        'card_id' => $card->id,
                        'technician' => $card->name,
                        'role' => $card->role,
                        'expires_at' => now()->timestamp + self::VERIFICATION_TIMEOUT,
                    ], self::VERIFICATION_TIMEOUT * 2);

                    $pending = $this->activeVerification($door);
                }

                return response()->json([
                    'granted' => false,
                    'pending' => true,
                    'name' => $pending['technician'],
                    'role' => strtoupper($pending['role']),
                    'timeout' => self::VERIFICATION_TIMEOUT,
                    'expires_in' => max(0, $pending['expires_at'] - now()->timestamp),
                    'message' => 'Tempelkan kartu Admin dalam 30 detik',
                ]);
            }

            if ($pending && $role !== 'admin') {
                DoorLog::create([
                    'uid' => $card->uid,
                    'name' => $card->name,
                    'role' => $card->role,
                    'door' => $door,
                    'direction' => $direction,
                    'method' => 'rfid',
                    'result' => 'denied',
                    'reason' => 'Menunggu tap kartu Admin untuk ' . $pending['technician'],
                    'at' => now(),
                ]);

                return response()->json([
                    'granted' => false,
                    'pending' => true,
                    'name' => $pending['technician'],
                    'role' => strtoupper($pending['role']),
                    'timeout' => self::VERIFICATION_TIMEOUT,
                    'expires_in' => max(0, $pending['expires_at'] - now()->timestamp),
                    'message' => 'Hanya kartu Admin yang dapat menyelesaikan verifikasi',
                ]);
            }

            $card->update(['last_used_at' => now()]);

            if ($role === 'admin' && $pending) {
                Cache::forget($this->verificationKey($door));

                DoorLog::query()->whereKey($pending['log_id'])->update([
                    'companion' => $card->name . ' (Admin)',
                    'method' => 'two_step',
                    'result' => 'granted',
                    'reason' => 'Akses diterima setelah verifikasi Admin',
                    'updated_at' => now(),
                ]);

                $technicianCard = RfidCard::find($pending['card_id'] ?? null);
                $technicianCard?->update(['last_used_at' => now()]);

                return response()->json([
                    'granted' => true,
                    'name' => $pending['technician'],
                    'role' => strtoupper($pending['role']),
                    'verified_by' => $card->name,
                    'message' => 'Akses teknisi diterima oleh Admin',
                ]);
            }

            DoorLog::create([
                'uid' => $card->uid,
                'name' => $card->name,
                'role' => $card->role,
                'door' => $door,
                'direction' => $direction,
                'method' => 'rfid',
                'result' => 'granted',
                'reason' => 'Akses diterima',
                'at' => now(),
            ]);

            return response()->json([
                'granted' => true,
                'name' => $card->name,
                'role' => strtoupper($card->role),
                'message' => 'Akses diterima',
            ]);
        }

        DoorLog::create([
            'uid' => $cleanUid,
            'name' => 'Kartu Tidak Dikenal',
            'role' => '-',
            'door' => $door,
            'direction' => $direction,
            'method' => 'rfid',
            'result' => 'denied',
            'reason' => 'Kartu belum terdaftar',
            'at' => now(),
        ]);

        return response()->json([
            'granted' => false,
            'name' => 'TIDAK DIKENAL',
            'role' => '-',
            'message' => 'Kartu tidak sah',
        ]);
    }

    private function verificationKey(string $door): string
    {
        return "door:{$door}:pending-verification";
    }

    private function activeVerification(string $door): ?array
    {
        $pending = Cache::get($this->verificationKey($door));

        if (! $pending) {
            return null;
        }

        if (($pending['expires_at'] ?? 0) <= now()->timestamp) {
            DoorLog::query()->whereKey($pending['log_id'] ?? null)
                ->where('result', 'pending')
                ->update([
                    'result' => 'denied',
                    'reason' => 'Waktu verifikasi Admin habis (30 detik)',
                    'updated_at' => now(),
                ]);

            Cache::forget($this->verificationKey($door));

            return null;
        }

        return $pending;
    }

    /**
     * Dipanggil oleh ESP32 untuk mencatat kejadian pintu (Tombol Exit & Alarm).
     * Endpoint: POST /api/door/log
     */
    public function log(Request $request): JsonResponse
    {
        $event = $request->input('event');
        $door = $request->input('door', 'gudang');

        if ($event === 'exit_button') {
            DoorLog::create([
                'uid' => null,
                'name' => 'Door Exit',
                'role' => 'Akses Keluar',
                'door' => $door,
                'direction' => 'out',
                'method' => 'exit_button',
                'result' => 'granted',
                'reason' => 'Keluar lewat tombol dalam',
                'at' => now(),
            ]);

            return response()->json(['status' => 'success', 'message' => 'Log Door Exit dicatat']);
        }

        if ($event === 'alarm_forced') {
            $log = DoorLog::create([
                'uid' => null,
                'name' => 'Peringatan Keamanan',
                'role' => 'Alarm',
                'door' => $door,
                'direction' => 'in',
                'method' => 'alarm',
                'result' => 'denied',
                'reason' => 'Pintu dibuka paksa!',
                'at' => now(),
            ]);

            Cache::forever('door:gudang:forced-open', [
                'id' => $log->id,
                'at' => $log->at->toIso8601String(),
                'message' => 'Pintu gudang dibuka paksa! Segera periksa lokasi.',
            ]);

            return response()->json(['status' => 'success', 'message' => 'Log Alarm dicatat']);
        }

        return response()->json(['status' => 'ignored'], 200);
    }

    /**
     * Menerima status relay dan sensor pintu dari ESP32.
     * Endpoint: POST /api/door/state
     */
    public function state(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'door' => ['required', 'in:gudang'],
            'door_open' => ['required', 'boolean'],
            'locked' => ['required', 'boolean'],
        ]);

        Cache::put('door:gudang:telemetry', [
            'door_open' => (bool) $validated['door_open'],
            'locked' => (bool) $validated['locked'],
            'last_seen' => now()->toIso8601String(),
        ], 20);

        if (! $validated['door_open']) {
            Cache::forget('door:gudang:forced-open');
        }

        return response()->json(['status' => 'received']);
    }

    /**
     * Status aktual perangkat gudang; status dianggap offline jika heartbeat berhenti.
     */
    public function status(): JsonResponse
    {
        $telemetry = Cache::get('door:gudang:telemetry');
        $verification = $this->activeVerification('gudang');
        $lastLog = DoorLog::query()->where('door', 'gudang')->latest('at')->first();
        $forcedOpen = Cache::get('door:gudang:forced-open');

        return response()->json([
            'doors' => [[
                'id' => 'gudang',
                'name' => 'Pintu gudang',
                'short' => 'Gudang',
                'online' => is_array($telemetry) && isset($telemetry['last_seen'])
                    && now()->diffInSeconds($telemetry['last_seen']) <= 15,
                'locked' => $telemetry['locked'] ?? true,
                'door_open' => $telemetry['door_open'] ?? null,
                'last_seen' => $telemetry['last_seen'] ?? null,
                'last_event' => $lastLog
                    ? $lastLog->name . ($lastLog->reason ? ': ' . $lastLog->reason : '')
                    : 'Belum ada aktivitas tercatat.',
                'forced_open' => $forcedOpen,
                'pending' => $verification ? [
                    'technician' => $verification['technician'],
                    'role' => $verification['role'],
                    'timeout' => self::VERIFICATION_TIMEOUT,
                    'expires_in' => max(0, $verification['expires_at'] - now()->timestamp),
                ] : null,
            ]],
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    /**
     * Mengelola sesi capture UID dari form kartu RFID.
     * Endpoint: GET /ajax/rfid/pending?action=start|cancel atau polling UID.
     */
    public function pending(Request $request): JsonResponse
    {
        $action = $request->query('action');

        if ($action === 'start') {
            Cache::forget('pending_rfid_tap');
            Cache::put('rfid_capture_active', true, 35);

            return response()->json(['status' => 'started']);
        }

        if ($action === 'cancel') {
            Cache::forget('pending_rfid_tap');
            Cache::forget('rfid_capture_active');

            return response()->json(['status' => 'cancelled']);
        }

        $pending = Cache::get('pending_rfid_tap');

        if ($pending) {
            Cache::forget('pending_rfid_tap');
            Cache::forget('rfid_capture_active');

            return response()->json([
                'uid' => $pending['uid'],
                'reader' => 'Reader Pintu Gudang',
            ]);
        }

        return response()->json([
            'uid' => null,
        ]);
    }
}
