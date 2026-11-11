@extends('layouts.guest')
@section('title', 'Daftar')

@section('content')
    <x-auth-shell title="Buat akun baru" subtitle="Isi data diri Anda. Kode verifikasi akan dikirim ke email." max="max-w-[520px]">
        <form method="POST" action="{{ route('register.store') }}" class="space-y-4" novalidate
              x-data="{
                  password: '',
                  confirm: '',
                  show: false,
                  submitting: false,
                  get score() {
                      const p = this.password;
                      return (p.length >= 8) + (/[a-z]/.test(p) && /[A-Z]/.test(p)) + /\d/.test(p) + /[^A-Za-z0-9]/.test(p);
                  },
                  get label() { return ['Terlalu lemah', 'Lemah', 'Cukup', 'Kuat', 'Sangat kuat'][this.password ? this.score : 0] },
                  get mismatch() { return this.confirm.length > 0 && this.confirm !== this.password },
              }"
              x-on:submit="submitting = true">
            @csrf

            <x-field label="Nama lengkap" name="name" required>
                <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" autofocus
                       class="input h-11 @error('name') input-error @enderror" placeholder="Dewi Lestari">
            </x-field>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Username" name="username" required hint="Huruf kecil, angka, titik, atau strip.">
                    <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" autocapitalize="none" spellcheck="false"
                           class="input h-11 @error('username') input-error @enderror" placeholder="dewi.noc">
                </x-field>
                <x-field label="No. telepon" name="phone" required hint="Contoh: 081234567890">
                    <input id="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone') }}" autocomplete="tel"
                           class="input h-11 @error('phone') input-error @enderror" placeholder="081234567890">
                </x-field>
            </div>

            <x-field label="Email" name="email" required>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" autocapitalize="none" spellcheck="false"
                       class="input h-11 @error('email') input-error @enderror" placeholder="nama@perusahaan.com">
            </x-field>

            <x-field label="Role" name="role" required hint="Menu yang bisa Anda akses mengikuti role ini.">
                <select id="role" name="role" class="input h-11 @error('role') input-error @enderror">
                    <option value="" disabled @selected(! old('role'))>Pilih role</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role }}" @selected(old('role') === $role)>{{ $role }}</option>
                    @endforeach
                </select>
            </x-field>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-field label="Kata sandi" name="password" required>
                    <div class="relative">
                        <input id="password" name="password" x-model="password" x-bind:type="show ? 'text' : 'password'" autocomplete="new-password"
                               class="input h-11 pr-12 @error('password') input-error @enderror">
                        <button type="button" class="absolute inset-y-1 right-1 grid w-10 place-items-center rounded text-ink-500 hover:bg-concrete-100 hover:text-ink-900"
                                x-on:click="show = !show" x-bind:aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                            <x-icon name="eye" :size="18" />
                        </button>
                    </div>
                </x-field>
                <x-field label="Ulangi kata sandi" name="password_confirmation" required>
                    <input id="password_confirmation" name="password_confirmation" x-model="confirm" x-bind:type="show ? 'text' : 'password'" autocomplete="new-password"
                           class="input h-11" x-bind:class="mismatch && 'input-error'">
                    <p class="error-text" x-show="mismatch" x-cloak>Kata sandi belum sama.</p>
                </x-field>
            </div>

            <div x-show="password.length" x-cloak aria-live="polite">
                <div class="flex gap-1.5" aria-hidden="true">
                    <template x-for="i in 4" :key="i">
                        <span class="h-1.5 flex-1 rounded-full transition-colors"
                              x-bind:class="i <= score ? (score <= 1 ? 'bg-bad-600' : score === 2 ? 'bg-warn-600' : 'bg-ok-600') : 'bg-concrete-200'"></span>
                    </template>
                </div>
                <p class="mt-1.5 text-[13px] text-ink-500">Kekuatan: <b class="text-ink-800" x-text="label"></b>. Minimal 8 karakter, kombinasi huruf dan angka.</p>
            </div>

            <x-button type="submit" class="h-11 w-full" loading="submitting">
                <span x-text="submitting ? 'Membuat akun' : 'Daftar & kirim kode OTP'"></span>
            </x-button>

            <p class="text-center text-sm text-ink-500">
                Sudah punya akun?
                <a href="{{ route('login') }}" class="font-semibold text-in-600 hover:underline">Masuk</a>
            </p>
        </form>
    </x-auth-shell>
@endsection