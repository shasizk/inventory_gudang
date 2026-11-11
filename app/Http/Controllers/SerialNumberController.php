<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\IncomingItem;
use App\Models\SerialNumber;
use App\Services\AutoNumber;
use App\Services\QrImage;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class SerialNumberController extends Controller
{
    /** Batas label dalam satu PDF "Unduh semua label" (menjaga waktu & memori server). */
    private const MAX_LABELS_ALL = 200;

    public function index(Request $request)
    {
        $serials = $this->filtered($request)
            ->with('category')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('pages.barang-masuk.serial-number', [
            'serials'    => $serials,
            'categories' => Category::orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeManage();

        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'item_name'   => ['required', 'string', 'max:120'],
            'serial'      => ['nullable', 'string', 'max:64', 'regex:/^[A-Za-z0-9._\/-]+$/'],
            'count'       => ['nullable', 'integer', 'min:1', 'max:50'],
        ], [
            'category_id.required' => 'Pilih kategori barang.',
            'category_id.exists'   => 'Kategori tidak ditemukan.',
            'item_name.required'   => 'Nama barang wajib diisi.',
            'item_name.max'        => 'Nama barang maksimal 120 karakter.',
            'serial.max'           => 'Serial number maksimal 64 karakter.',
            'serial.regex'         => 'Serial number hanya boleh huruf, angka, titik, garis miring, dan strip.',
            'count.min'            => 'Jumlah unit minimal 1.',
            'count.max'            => 'Jumlah unit maksimal 50 per simpan.',
        ]);

        $serial = trim((string) ($data['serial'] ?? ''));
        $count  = max(1, (int) ($data['count'] ?? 1));
        $by     = $this->actor();

        // Daftar serial yang akan dibuat. null = serial dikosongkan -> pakai nomor SN otomatis.
        if ($serial === '') {
            $serials = array_fill(0, $count, null);
        } else {
            $serials = $this->serialRange($serial, $count);

            if ($serials === null) {
                return back()->withInput()->withErrors(['serial' =>
                    'Agar bisa dibuat berurutan, serial number harus diakhiri angka (contoh: A003) dan angkanya maksimal 15 digit.']);
            }

            $taken = SerialNumber::whereIn('serial', $serials)->pluck('serial')->all();
            if ($taken) {
                $message = ($count === 1)
                    ? 'Serial number sudah terdaftar. Gunakan serial number lain.'
                    : 'Serial number sudah terdaftar: ' . implode(', ', array_slice($taken, 0, 5)) . (count($taken) > 5 ? ', ...' : '')
                        . '. Ubah serial awal atau jumlah unit.';

                return back()->withInput()->withErrors(['serial' => $message]);
            }
        }

        $ids = [];

        try {
            DB::transaction(function () use ($data, $serials, $by, &$ids) {
                $ids = [];

                foreach ($serials as $serial) {
                    $snNo = $this->nextSnNo($by);

                    $unit = SerialNumber::create([
                        'sn_no'       => $snNo,
                        'serial'      => $serial ?? $snNo,
                        'item_name'   => $data['item_name'],
                        'category_id' => $data['category_id'],
                        'source'      => 'sistem',
                        'status'      => 'gudang',
                        'created_by'  => $by,
                    ]);

                    // Setiap serial number yang disimpan otomatis tercatat sebagai barang masuk.
                    IncomingItem::create([
                        'serial_number_id' => $unit->id,
                        'category_id'      => $unit->category_id,
                        'item_name'        => $unit->item_name,
                        'qty'              => 1,
                        'received_at'      => now(),
                        'created_by'       => $by,
                    ]);

                    $ids[] = $unit->id;
                }
            });
        } catch (UniqueConstraintViolationException) {
            // Balapan: serial yang sama disimpan orang lain di detik yang sama. Transaksi (dan counter) dibatalkan.
            return back()->withInput()->withErrors(['serial' => 'Serial number sudah terdaftar. Gunakan serial number lain.']);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()
            ->with('success', count($ids) . ' serial number disimpan dan dicatat sebagai barang masuk.')
            ->with('label_ids', $ids);
    }

    public function destroy(int $id)
    {
        $this->authorizeManage();
        $unit = SerialNumber::findOrFail($id);

        if ($unit->status === 'keluar') {
            return back()->with('error', 'Unit ini sudah keluar gudang, serial number tidak bisa dihapus.');
        }

        $unit->delete(); // baris barang masuknya ikut terhapus (cascade). Counter autonumber sengaja tidak mundur.

        return back()->with('success', 'Serial number dihapus.');
    }

    /** PDF label untuk unit tertentu. Contoh: /barang-masuk/serial-number/label?ids[]=1&ids[]=2 */
    public function label(Request $request)
    {
        $ids = collect((array) $request->query('ids', []))
            ->map(fn ($v) => (int) $v)->filter()->unique()->take(100)->values();

        abort_if($ids->isEmpty(), 404, 'Tidak ada serial number yang dipilih.');

        $serials = SerialNumber::whereIn('id', $ids)->orderBy('id')->get();
        abort_if($serials->isEmpty(), 404, 'Serial number tidak ditemukan.');

        return $this->labelResponse($serials);
    }

    /** Satu PDF berisi semua label yang cocok dengan pencarian/filter saat ini (tanpa filter = semua). */
    public function labelAll(Request $request)
    {
        $query = $this->filtered($request);
        $total = (clone $query)->count();

        if ($total === 0) {
            return back()->with('error', 'Tidak ada serial number untuk dicetak.');
        }

        if ($total > self::MAX_LABELS_ALL) {
            return back()->with('error', "Ada {$total} label, melebihi batas " . self::MAX_LABELS_ALL
                . ' per PDF. Persempit dengan pencarian atau filter, lalu unduh lagi.');
        }

        @set_time_limit(120);

        return $this->labelResponse($query->orderBy('id')->get());
    }

    /** Query daftar dengan pencarian & filter yang sama untuk tabel dan "Unduh semua label". */
    private function filtered(Request $request): Builder
    {
        $q = trim((string) $request->query('q', ''));

        return SerialNumber::query()
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('serial', 'like', "%{$q}%")
                ->orWhere('sn_no', 'like', "%{$q}%")
                ->orWhere('item_name', 'like', "%{$q}%")))
            ->when(in_array($request->query('source'), ['pabrik', 'sistem'], true),
                fn ($query) => $query->where('source', $request->query('source')))
            ->when(in_array($request->query('status'), ['gudang', 'keluar'], true),
                fn ($query) => $query->where('status', $request->query('status')));
    }

    private function labelResponse(Collection $serials)
    {
        $qrs = $serials->mapWithKeys(fn ($s) => [$s->id => base64_encode(QrImage::png($s->serial))]);

        return Pdf::loadView('pdf.serial-labels', compact('serials', 'qrs'))
            ->setPaper('a4', 'portrait')
            ->download('label-serial-' . now()->format('Ymd-His') . '.pdf');
    }

    /**
     * Buat daftar serial berurutan dari angka di belakang serial awal, panjang angka dipertahankan.
     * A003 x3 -> A003, A004, A005.   ABC-0099 x2 -> ABC-0099, ABC-0100.
     *
     * @return string[]|null  null bila serial tidak berakhiran angka (atau angkanya terlalu panjang) padahal butuh lebih dari 1
     */
    private function serialRange(string $serial, int $count): ?array
    {
        if ($count === 1) {
            return [$serial];
        }

        if (! preg_match('/^(.*?)(\d+)$/', $serial, $m) || strlen($m[2]) > 15) {
            return null;
        }

        $width = strlen($m[2]);
        $start = (int) $m[2];

        return array_map(
            fn ($i) => $m[1] . str_pad((string) ($start + $i), $width, '0', STR_PAD_LEFT),
            range(0, $count - 1),
        );
    }

    /** Ambil nomor SN berikutnya; lewati bila kebetulan sama dengan serial yang pernah diketik manual. */
    private function nextSnNo(?string $by): string
    {
        for ($try = 0; $try < 5; $try++) {
            $snNo = AutoNumber::next('sn_no', $by);

            $taken = SerialNumber::where('serial', $snNo)->orWhere('sn_no', $snNo)->exists();
            if (! $taken) {
                return $snNo;
            }
        }

        throw new RuntimeException('Gagal membuat nomor SN yang unik. Periksa aturan sn_no di tabel __autonumber_config.');
    }

    /** Sama dengan KategoriController. Ganti dengan middleware/policy setelah login siap. */
    private function authorizeManage(): void
    {
        $role = strtolower((string) (auth()->user()->role ?? (config('app.ui_demo', app()->isLocal()) ? 'owner' : 'guest')));
        abort_unless(in_array($role, ['owner', 'admin'], true), 403, 'Anda tidak punya hak mengelola serial number.');
    }

    private function actor(): ?string
    {
        $user = auth()->user();

        return $user ? Str::limit((string) ($user->username ?? $user->name), 20, '') : null;
    }
}