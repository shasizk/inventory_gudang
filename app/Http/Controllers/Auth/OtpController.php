<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OtpController extends Controller
{
    public function show(OtpService $otp)
    {
        $email = $this->pendingEmail();
        if (! $email) {
            return redirect()->route('login');
        }

        return view('pages.auth.otp', [
            'email' => $email,
            'masked' => $this->mask($email),
            'resendIn' => $otp->resendIn($email),
            'expiresIn' => $otp->expiresIn($email),
        ]);
    }

    public function verify(Request $request, OtpService $otp)
    {
        $email = $this->pendingEmail();
        if (! $email) {
            return redirect()->route('login');
        }

        $request->validate(
            ['code' => ['required', 'digits:6']],
            ['code.required' => 'Masukkan 6 digit kode OTP.', 'code.digits' => 'Kode OTP harus 6 digit angka.'],
        );

        $status = $otp->verify($email, $request->input('code'));

        if ($status === 'ok') {
            User::where('email', $email)->update(['email_verified_at' => now()]);
            session()->forget('otp_email');

            return redirect()->route('login')
                ->with('success', 'Email terverifikasi. Silakan masuk.')
                ->with('login_email', $email);
        }

        $message = match ($status) {
            'invalid' => 'Kode OTP salah. Sisa percobaan: ' . $otp->attemptsLeft($email) . '.',
            'expired' => 'Kode OTP sudah kedaluwarsa. Kirim ulang kode baru.',
            'locked' => 'Terlalu banyak salah memasukkan kode. Kirim ulang kode baru.',
            default => 'Kode OTP tidak ditemukan. Kirim ulang kode baru.',
        };

        return back()->withErrors(['code' => $message]);
    }

    public function resend(OtpService $otp)
    {
        $email = $this->pendingEmail();
        if (! $email) {
            return redirect()->route('login');
        }

        if (($wait = $otp->resendIn($email)) > 0) {
            return back()->with('otp_error', "Tunggu {$wait} detik sebelum meminta kode lagi.");
        }

        $user = User::where('email', $email)->first();

        return $otp->issue($user)
            ? back()->with('success', 'Kode OTP baru sudah dikirim.')
            : back()->with('otp_error', 'Email gagal dikirim. Periksa pengaturan SMTP lalu coba lagi.');
    }

    private function pendingEmail(): ?string
    {
        $email = session('otp_email');

        return $email && User::where('email', $email)->whereNull('email_verified_at')->exists() ? $email : null;
    }

    private function mask(string $email): string
    {
        [$local, $domain] = explode('@', $email, 2);

        return Str::substr($local, 0, 2) . str_repeat('*', max(1, mb_strlen($local) - 2)) . '@' . $domain;
    }
}