@extends('layouts.guest')
@section('title', 'Masuk')

@section('content')
    @php $throttle = (int) session('throttle_seconds', 0); @endphp

    <x-auth-shell title="Masuk ke akun" subtitle="Gunakan email dan kata sandi yang sudah Anda daftarkan.">
        <form method="POST" action="{{ route('login.store') }}" class="space-y-4" novalidate
              x-data="{
                  email: @js(old('email', session('login_email', ''))),
                  password: '',
                  show: false,
                  caps: false,
                  submitting: false,
                  wait: {{ $throttle }},
                  touched: false,
                  init() {
                      if (this.wait > 0) {
                          const t = setInterval(() => { this.wait--; if (this.wait <= 0) clearInterval(t) }, 1000);
                      }
                  },
                  get missing() { return !this.email.trim() ? 'email' : (!this.password ? 'password' : null) },
                  submit(e) {
                      this.touched = true;
                      if (this.missing || this.wait > 0 || this.submitting) { e.preventDefault(); this.$refs[this.missing ?? 'email']?.focus(); return }
                      this.submitting = true;
                  },
              }"
              x-on:submit="submit($event)">
            @csrf

            @if ($errors->any())
                <div class="flex items-start gap-2.5 rounded-md bg-bad-50 px-3.5 py-3 text-sm text-bad-700" role="alert">
                    <x-icon name="alert" :size="18" class="mt-0.5" />
                    <p class="font-semibold">{{ $errors->first() }}</p>
                </div>
            @endif
            <div x-show="wait > 0" x-cloak class="flex items-start gap-2.5 rounded-md bg-warn-50 px-3.5 py-3 text-sm text-warn-700" role="status">
                <x-icon name="clock" :size="18" class="mt-0.5" />
                <p>Coba lagi dalam <b class="num" x-text="wait"></b> detik.</p>
            </div>

            <x-field label="Email" for="email">
                <input id="email" name="email" x-ref="email" x-model="email" type="email" autocomplete="email" autocapitalize="none" spellcheck="false" autofocus
                       class="input h-11 @error('email') input-error @enderror" x-bind:class="touched && missing === 'email' && 'input-error'"
                       placeholder="nama@perusahaan.com">
                <p class="error-text" x-show="touched && missing === 'email'" x-cloak>Isi email Anda.</p>
            </x-field>

            <x-field label="Kata sandi" for="password">
                <div class="relative">
                    <input id="password" name="password" x-ref="password" x-model="password" x-bind:type="show ? 'text' : 'password'" autocomplete="current-password"
                           x-on:keydown="caps = $event.getModifierState?.('CapsLock')" x-on:keyup="caps = $event.getModifierState?.('CapsLock')" x-on:blur="caps = false"
                           class="input h-11 pr-12" x-bind:class="touched && missing === 'password' && 'input-error'">
                    <button type="button" class="absolute inset-y-1 right-1 grid w-10 place-items-center rounded text-ink-500 hover:bg-concrete-100 hover:text-ink-900"
                            x-on:click="show = !show; $refs.password.focus()" x-bind:aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'" x-bind:aria-pressed="show">
                        <x-icon name="eye" :size="18" />
                    </button>
                </div>
                <p class="error-text" x-show="touched && missing === 'password'" x-cloak>Isi kata sandi Anda.</p>
                <p class="mt-1.5 flex items-center gap-1.5 text-[13px] font-medium text-warn-700" x-show="caps" x-cloak>
                    <x-icon name="alert" :size="14" /> Caps Lock aktif
                </p>
            </x-field>

            <label class="flex items-center gap-2.5">
                <input type="checkbox" name="remember" value="1" class="check" @checked(old('remember'))>
                <span>Tetap masuk di perangkat ini</span>
            </label>

            <x-button type="submit" class="h-11 w-full" loading="submitting" x-bind:disabled="wait > 0">
                <span x-text="submitting ? 'Memeriksa akun' : 'Masuk'"></span>
            </x-button>

            <p class="text-center text-sm text-ink-500">
                Belum punya akun?
                <a href="{{ route('register') }}" class="font-semibold text-in-600 hover:underline">Daftar sekarang</a>
            </p>
            <p class="text-center text-[13px] text-ink-500">Lupa kata sandi? Hubungi Admin gudang untuk mengatur ulang.</p>
        </form>
    </x-auth-shell>
@endsection