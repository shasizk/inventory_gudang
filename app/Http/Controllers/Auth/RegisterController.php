<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RegisterController extends Controller
{
    public function create()
    {
        return view('pages.auth.register', ['roles' => User::roleOptions()]);
    }

    public function store(Request $request, OtpService $otp)
    {
        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'username' => Str::lower(trim((string) $request->input('username'))),
            'phone' => preg_replace('/[\s\-]/', '', (string) $request->input('phone')),
        ]);

        // Akun yang belum diverifikasi OTP tidak punya akses apa pun, jadi boleh didaftarkan ulang
        // (misal salah ketik email saat daftar pertama).
        User::whereNull('email_verified_at')
            ->where(fn ($q) => $q->where('email', $request->input('email'))->orWhere('username', $request->input('username')))
            ->delete();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-z0-9._-]+$/', 'unique:users,username'],
            'email' => ['required', 'email:rfc', 'max:150', 'unique:users,email'],
            'phone' => ['required', 'regex:/^(\+62|62|0)8[0-9]{8,12}$/'],
            'role' => ['required', Rule::in(User::roleOptions())],
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/', 'confirmed'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.min' => 'Username minimal 3 karakter.',
            'username.regex' => 'Username hanya boleh huruf, angka, titik, strip, dan garis bawah.',
            'username.unique' => 'Username sudah dipakai.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar. Silakan masuk.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.regex' => 'Nomor telepon tidak valid. Contoh: 081234567890.',
            'role.required' => 'Pilih role Anda.',
            'role.in' => 'Role tidak valid.',
            'password.required' => 'Kata sandi wajib diisi.',
            'password.min' => 'Kata sandi minimal 8 karakter.',
            'password.regex' => 'Kata sandi harus mengandung huruf dan angka.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        $user = User::create($data); // password otomatis di-hash oleh cast di model

        session(['otp_email' => $user->email]);

        if (! $otp->issue($user)) {
            return redirect()->route('otp.show')
                ->with('otp_error', 'Akun dibuat, tetapi email OTP gagal dikirim. Coba kirim ulang kode.');
        }

        return redirect()->route('otp.show')->with('success', 'Kode OTP sudah dikirim ke email Anda.');
    }
}