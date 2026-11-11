@extends('layouts.guest')
@section('title', 'Verifikasi email')

@section('content')
    <x-auth-shell title="Verifikasi email" :subtitle="'Kami mengirim kode 6 digit ke ' . $masked . '. Kode berlaku ' . \App\Services\OtpService::TTL_MINUTES . ' menit.'">
        <div x-data="{
                 digits: ['', '', '', '', '', ''],
                 submitting: false,
                 wait: {{ $resendIn }},
                 resending: false,
                 init() {
                     setInterval(() => { if (this.wait > 0) this.wait-- }, 1000);
                     this.$nextTick(() => this.$refs.d0.focus());
                 },
                 get code() { return this.digits.join('') },
                 type(i, e) {
                     const v = e.target.value.replace(/\D/g, '');
                     if (v.length > 1) { this.fill(v); return }
                     this.digits[i] = v;
                     e.target.value = v;
                     if (v && i < 5) this.$refs['d' + (i + 1)].focus();
                     this.auto();
                 },
                 back(i, e) {
                     if (e.key === 'Backspace' && !this.digits[i] && i > 0) this.$refs['d' + (i - 1)].focus();
                 },
                 paste(e) { e.preventDefault(); this.fill(e.clipboardData.getData('text') || '') },
                 fill(text) {
                     const v = text.replace(/\D/g, '').slice(0, 6).split('');
                     this.digits = Array.from({ length: 6 }, (_, i) => v[i] ?? '');
                     this.$refs['d' + Math.min(v.length, 5)].focus();
                     this.auto();
                 },
                 auto() {
                     if (this.code.length === 6 && !this.submitting) {
                         this.submitting = true;
                         this.$nextTick(() => this.$refs.form.submit());
                     }
                 },
             }">

            @if ($errors->any() || session('otp_error'))
                <div class="mb-4 flex items-start gap-2.5 rounded-md bg-bad-50 px-3.5 py-3 text-sm text-bad-700" role="alert">
                    <x-icon name="alert" :size="18" class="mt-0.5" />
                    <p class="font-semibold">{{ $errors->first() ?: session('otp_error') }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('otp.verify') }}" x-ref="form" class="space-y-5" x-on:submit="submitting = true">
                @csrf
                <input type="hidden" name="code" x-bind:value="code">

                <div>
                    <p class="label" id="otp-label">Kode OTP</p>
                    <div class="flex justify-between gap-2" role="group" aria-labelledby="otp-label">
                        @for ($i = 0; $i < 6; $i++)
                            <input type="text" inputmode="numeric" autocomplete="{{ $i === 0 ? 'one-time-code' : 'off' }}"
                                   x-ref="d{{ $i }}" x-bind:value="digits[{{ $i }}]"
                                   x-on:input="type({{ $i }}, $event)" x-on:keydown="back({{ $i }}, $event)" x-on:paste="paste($event)"
                                   x-on:focus="$event.target.select()"
                                   aria-label="Digit {{ $i + 1 }}"
                                   class="input h-14 w-full text-center font-mono text-2xl font-semibold @error('code') input-error @enderror">
                        @endfor
                    </div>
                </div>

                <x-button type="submit" class="h-11 w-full" loading="submitting" x-bind:disabled="code.length < 6">
                    <span x-text="submitting ? 'Memverifikasi' : 'Verifikasi'"></span>
                </x-button>
            </form>

            <form method="POST" action="{{ route('otp.resend') }}" class="mt-5 text-center text-sm text-ink-500" x-on:submit="resending = true">
                @csrf
                Belum menerima kode?
                <button type="submit" class="font-semibold text-in-600 hover:underline disabled:cursor-not-allowed disabled:text-ink-400 disabled:no-underline"
                        x-bind:disabled="wait > 0 || resending">
                    <span x-show="wait <= 0">Kirim ulang</span>
                    <span x-show="wait > 0" x-cloak>Kirim ulang dalam <span class="num" x-text="wait"></span> dtk</span>
                </button>
            </form>

            <p class="mt-3 text-center text-[13px] text-ink-500">Periksa juga folder spam. <a href="{{ route('login') }}" class="font-semibold text-ink-700 hover:underline">Kembali ke halaman masuk</a></p>
        </div>
    </x-auth-shell>
@endsection