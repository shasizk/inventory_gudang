<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class LoginController extends Controller
{
    public function create()
    {
        return view('pages.auth.login');
    }

    public function store(Request $request, OtpService $otp)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Isi email Anda.',
            'email.email' => 'Format email tidak valid.',
            'password.required' => 'Isi kata sandi Anda.',
        ]);

        $key = 'login:' . Str::lower($data['email']) . '|' . $request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return back()
                ->withErrors(['email' => 'Terlalu banyak percobaan masuk.'])
                ->onlyInput('email')
                ->with('throttle_seconds', RateLimiter::availableIn($key));
        }

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            return back()->withErrors(['email' => 'Email atau kata sandi salah.'])->onlyInput('email');
        }

        // Password benar tapi email belum diverifikasi: kirim OTP lalu arahkan ke halaman OTP
        if (! $user->hasVerifiedEmail()) {
            session(['otp_email' => $user->email]);
            $sent = $otp->resendIn($user->email) > 0 ? true : $otp->issue($user);

            return redirect()->route('otp.show')->with(
                $sent ? 'success' : 'otp_error',
                $sent ? 'Akun belum terverifikasi. Masukkan kode OTP yang dikirim ke email Anda.'
                      : 'Akun belum terverifikasi dan email OTP gagal dikirim. Coba kirim ulang.'
            );
        }

        RateLimiter::clear($key);
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda sudah keluar dari akun.');
    }
}