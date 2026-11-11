<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function edit(Request $request)
    {
        return view('pages.profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->merge([
            'email' => Str::lower(trim((string) $request->input('email'))),
            'phone' => preg_replace('/[\s\-]/', '', (string) $request->input('phone')),
        ]);

        $emailChanged = $request->input('email') !== Str::lower($user->email);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'regex:/^(\+62|62|0)8[0-9]{8,12}$/'],
            'email' => ['required', 'email:rfc', 'max:150', Rule::unique('users', 'email')->ignore($user->id)],
            'email_password' => [Rule::requiredIf($emailChanged), 'nullable', 'current_password'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.regex' => 'Nomor telepon tidak valid. Contoh: 081234567890.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah dipakai akun lain.',
            'email_password.required' => 'Masukkan kata sandi saat ini untuk mengganti email.',
            'email_password.current_password' => 'Kata sandi saat ini salah.',
        ]);

        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
        ]);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    public function password(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'regex:/^(?=.*[A-Za-z])(?=.*\d).+$/', 'confirmed', 'different:current_password'],
        ], [
            'current_password.required' => 'Masukkan kata sandi saat ini.',
            'current_password.current_password' => 'Kata sandi saat ini salah.',
            'password.required' => 'Kata sandi baru wajib diisi.',
            'password.min' => 'Kata sandi baru minimal 8 karakter.',
            'password.regex' => 'Kata sandi baru harus mengandung huruf dan angka.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sama.',
            'password.different' => 'Kata sandi baru tidak boleh sama dengan yang lama.',
        ]);

        $request->user()->update(['password' => $data['password']]);

        return back()->with('success', 'Kata sandi berhasil diubah.');
    }

    public function photo(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'photo.required' => 'Pilih foto terlebih dahulu.',
            'photo.image' => 'File harus berupa gambar.',
            'photo.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto maksimal 2 MB.',
            'photo.uploaded' => 'Foto gagal diunggah. Ukuran maksimal 2 MB.',
        ]);

        $user = $request->user();
        $path = $request->file('photo')->store('profile-photos', 'public');

        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
        }
        $user->update(['photo' => $path]);

        return back()->with('success', 'Foto profil diperbarui.');
    }

    public function removePhoto(Request $request)
    {
        $user = $request->user();

        if ($user->photo) {
            Storage::disk('public')->delete($user->photo);
            $user->update(['photo' => null]);
        }

        return back()->with('success', 'Foto profil dihapus.');
    }
}