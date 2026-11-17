<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    private const CHECK_OUT_START_HOUR = 14;
    private const CHECK_OUT_START_MINUTE = 20;

    public function index(Request $request)
    {
        $date = $request->query('date', now()->toDateString());
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'role' => ['nullable', Rule::in(['teknisi', 'noc'])],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();
        $canViewAll = $this->canViewAll($user);
        $query = Attendance::query()
            ->with('user')
            ->whereDate('attendance_date', $date);

        if (! $canViewAll) {
            $query->where('user_id', $user->id);
        } else {
            if (! empty($filters['role'])) {
                $query->whereHas('user', fn ($users) => $users->whereRaw('LOWER(role) = ?', [$filters['role']]));
            }

            if (! empty($filters['q'])) {
                $query->whereHas('user', fn ($users) => $users->where('name', 'like', '%' . $filters['q'] . '%'));
            }
        }

        $total = (clone $query)->count();
        $complete = (clone $query)->whereNotNull('check_out_at')->count();
        $attendance = $query->orderByDesc('check_in_at')->paginate(15)->withQueryString();
        $todayAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('attendance_date', now()->toDateString())
            ->first();

        return view('pages.absensi.index', [
            'attendance' => $attendance,
            'attendanceDate' => $date,
            'todayAttendance' => $todayAttendance,
            'canSubmit' => in_array(strtolower((string) $user->role), ['teknisi', 'noc'], true),
            'canViewAll' => $canViewAll,
            'checkoutAllowed' => now()->greaterThanOrEqualTo($this->checkOutOpensAt()),
            'checkoutStartTime' => $this->checkOutOpensAt()->format('H:i'),
            'total' => $total,
            'complete' => $complete,
        ]);
    }

    public function checkIn(Request $request)
    {
        $user = $request->user();
        $this->authorizeAttendance($user);
        $this->validatedEvidence($request);
        $now = now();
        $photoPath = $request->file('photo')->store('attendance/' . $now->toDateString(), 'local');

        if (! $photoPath) {
            return back()->withInput()->with('error', 'Foto gagal disimpan. Coba ambil foto lagi.');
        }

        try {
            Attendance::create([
                'user_id' => $user->id,
                'attendance_date' => $now->toDateString(),
                'check_in_at' => $now,
                'check_in_photo' => $photoPath,
            ]);
        } catch (UniqueConstraintViolationException) {
            Storage::disk('local')->delete($photoPath);

            return back()->with('error', 'Anda sudah melakukan check-in hari ini.');
        }

        return back()->with('success', 'Check-in berhasil dicatat dengan foto bukti.');
    }

    public function checkOut(Request $request)
    {
        $user = $request->user();
        $this->authorizeAttendance($user);
        $this->validatedEvidence($request);
        $photoPath = null;

        try {
            $result = DB::transaction(function () use ($request, $user, &$photoPath) {
                $now = now();
                if ($now->lt($this->checkOutOpensAt($now))) {
                    return 'too_early';
                }

                $attendance = Attendance::where('user_id', $user->id)
                    ->whereDate('attendance_date', $now->toDateString())
                    ->lockForUpdate()
                    ->first();

                if (! $attendance) {
                    return 'missing';
                }

                if ($attendance->check_out_at) {
                    return 'complete';
                }

                $photoPath = $request->file('photo')->store('attendance/' . $now->toDateString(), 'local');
                if (! $photoPath) {
                    throw new \RuntimeException('Foto gagal disimpan. Coba ambil foto lagi.');
                }

                $attendance->update([
                    'check_out_at' => $now,
                    'check_out_photo' => $photoPath,
                ]);

                return 'saved';
            });
        } catch (\Throwable $exception) {
            if ($photoPath) {
                Storage::disk('local')->delete($photoPath);
            }

            throw $exception;
        }

        if ($result === 'missing') {
            return back()->with('error', 'Anda harus check-in terlebih dahulu sebelum check-out.');
        }

        if ($result === 'too_early') {
            return back()->with('error', 'Check-out baru dapat dilakukan mulai pukul 12:50.');
        }

        if ($result === 'complete') {
            return back()->with('error', 'Anda sudah melakukan check-out hari ini.');
        }

        return back()->with('success', 'Check-out berhasil dicatat dengan foto bukti.');
    }

    public function photo(Request $request, Attendance $attendance, string $type)
    {
        abort_unless(in_array($type, ['check-in', 'check-out'], true), 404);
        abort_unless($this->canViewAll($request->user()) || $attendance->user_id === $request->user()->id, 403);

        $path = $type === 'check-in' ? $attendance->check_in_photo : $attendance->check_out_photo;
        abort_if(! $path || ! Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function validatedEvidence(Request $request): array
    {
        return $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'consent' => ['accepted'],
        ], [
            'photo.required' => 'Ambil foto bukti terlebih dahulu.',
            'photo.image' => 'Bukti harus berupa gambar.',
            'photo.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
            'consent.accepted' => 'Setujui penyimpanan foto bukti sebelum mengirim absensi.',
        ]);
    }

    private function authorizeAttendance(User $user): void
    {
        abort_unless(in_array(strtolower((string) $user->role), ['teknisi', 'noc'], true), 403, 'Hanya akun Teknisi dan NOC yang dapat mencatat absensi.');
    }

    private function canViewAll(User $user): bool
    {
        return in_array(strtolower((string) $user->role), ['owner', 'admin'], true);
    }

    private function checkOutOpensAt(?\Illuminate\Support\Carbon $now = null): \Illuminate\Support\Carbon
    {
        return ($now ?? now())->copy()
            ->startOfDay()
            ->setTime(self::CHECK_OUT_START_HOUR, self::CHECK_OUT_START_MINUTE);
    }
}
