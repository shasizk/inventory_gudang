@extends('layouts.app')
@section('title', 'My Profile')
@section('section', 'Akun / My Profile')

@section('content')
    @php
        $roleBadge = ['Admin' => 'badge-dark', 'NOC' => 'badge-in', 'Teknisi' => 'badge-out'][$user->role] ?? 'badge-neutral';
    @endphp

    <x-page-header title="My Profile" description="Kelola informasi akun, foto profil, dan keamanan kata sandi Anda." />

    <div class="grid items-start gap-6 lg:grid-cols-[320px_minmax(0,1fr)]">

        {{-- ============ Kartu identitas + foto ============ --}}
        <aside class="panel overflow-hidden lg:sticky lg:top-24"
               x-data="{
                   preview: null,
                   error: '',
                   pick(e) {
                       const f = e.target.files[0];
                       this.error = '';
                       if (!f) return;
                       if (!['image/jpeg', 'image/png', 'image/webp'].includes(f.type)) { this.error = 'Format harus JPG, PNG, atau WEBP.'; e.target.value = ''; return }
                       if (f.size > 2 * 1024 * 1024) { this.error = 'Ukuran foto maksimal 2 MB.'; e.target.value = ''; return }
                       this.preview = URL.createObjectURL(f);
                   },
                   cancel() { this.preview = null; this.error = ''; this.$refs.file.value = '' },
                   saving: false,
               }">
            <div class="hazard-edge h-1.5" aria-hidden="true"></div>
            <div class="flex flex-col items-center px-6 pt-8 pb-6 text-center">
                <div class="relative">
                    <template x-if="preview">
                        <img x-bind:src="preview" alt="Pratinjau foto baru" class="size-32 rounded-2xl object-cover ring-4 ring-concrete-100">
                    </template>
                    <template x-if="!preview">
                        <div>
                            @if ($user->photo_url)
                                <img src="{{ $user->photo_url }}" alt="Foto profil {{ $user->name }}" class="size-32 rounded-2xl object-cover ring-4 ring-concrete-100">
                            @else
                                <span class="grid size-32 place-items-center rounded-2xl bg-ink-900 font-display text-4xl font-semibold text-hazard-500 ring-4 ring-concrete-100">{{ $user->initials }}</span>
                            @endif
                        </div>
                    </template>
                </div>

                <h2 class="mt-5 font-display text-2xl leading-tight font-semibold">{{ $user->name }}</h2>
                <p class="mt-0.5 text-ink-500">&#64;{{ $user->username }}</p>
                <span class="badge {{ $roleBadge }} mt-3">{{ $user->role }}</span>

                <form method="POST" action="{{ route('profile.photo') }}" enctype="multipart/form-data" class="mt-6 w-full" x-on:submit="saving = true">
                    @csrf
                    <input type="file" name="photo" accept="image/jpeg,image/png,image/webp" class="sr-only" x-ref="file" x-on:change="pick($event)">

                    <div x-show="!preview" class="flex flex-col gap-2">
                        <button type="button" class="btn btn-secondary w-full" x-on:click="$refs.file.click()">
                            <x-icon name="edit" :size="16" /> {{ $user->photo ? 'Ganti foto' : 'Unggah foto' }}
                        </button>
                    </div>
                    <div x-show="preview" x-cloak class="flex gap-2">
                        <x-button type="submit" size="sm" class="flex-1" loading="saving">Simpan foto</x-button>
                        <button type="button" class="btn btn-ghost btn-sm" x-on:click="cancel()">Batal</button>
                    </div>

                    <p class="error-text" x-show="error" x-text="error" x-cloak></p>
                    @error('photo') <p class="error-text">{{ $message }}</p> @enderror
                    <p class="hint" x-show="!preview">JPG, PNG, atau WEBP. Maksimal 2 MB.</p>
                </form>

                @if ($user->photo)
                    <form method="POST" action="{{ route('profile.photo.remove') }}" class="mt-1" x-show="!preview"
                          x-on:submit="if (!confirm('Hapus foto profil Anda?')) $event.preventDefault()">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-ghost btn-sm text-bad-700">Hapus foto</button>
                    </form>
                @endif
            </div>

            <dl class="divide-y divide-concrete-200 border-t border-concrete-200 text-sm">
                <div class="flex items-center justify-between gap-4 px-6 py-3">
                    <dt class="text-ink-500">Email</dt>
                    <dd class="min-w-0 truncate font-medium" title="{{ $user->email }}">{{ $user->email }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-6 py-3">
                    <dt class="text-ink-500">Telepon</dt>
                    <dd class="font-medium">{{ $user->phone }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-6 py-3">
                    <dt class="text-ink-500">Status email</dt>
                    <dd>
                        @if ($user->hasVerifiedEmail())
                            <span class="badge badge-ok">Terverifikasi</span>
                        @else
                            <span class="badge badge-warn">Belum diverifikasi</span>
                        @endif
                    </dd>
                </div>
                <div class="flex items-center justify-between gap-4 px-6 py-3">
                    <dt class="text-ink-500">Bergabung</dt>
                    <dd class="font-medium">{{ $user->created_at?->translatedFormat('d F Y') }}</dd>
                </div>
            </dl>
        </aside>

        {{-- ============ Form ============ --}}
        <div class="space-y-6">

            <x-panel title="Informasi profil" description="Data ini tampil di akun Anda. Username dan role hanya bisa diubah oleh Admin.">
                <form method="POST" action="{{ route('profile.update') }}" class="space-y-4 p-5" novalidate
                      x-data="{
                          email: @js(old('email', $user->email)),
                          original: @js($user->email),
                          saving: false,
                          get changed() { return this.email.trim().toLowerCase() !== this.original.toLowerCase() },
                      }"
                      x-on:submit="saving = true">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="Nama lengkap" name="name" required class="sm:col-span-2">
                            <input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" autocomplete="name"
                                   class="input h-11 @error('name') input-error @enderror">
                        </x-field>

                        <x-field label="Email" name="email" required>
                            <input id="email" name="email" type="email" x-model="email" autocomplete="email" autocapitalize="none"
                                   class="input h-11 @error('email') input-error @enderror">
                        </x-field>

                        <x-field label="No. telepon" name="phone" required>
                            <input id="phone" name="phone" type="tel" inputmode="tel" value="{{ old('phone', $user->phone) }}" autocomplete="tel"
                                   class="input h-11 @error('phone') input-error @enderror">
                        </x-field>

                        <x-field label="Username" for="username_ro">
                            <input id="username_ro" type="text" value="{{ $user->username }}" class="input h-11" disabled>
                        </x-field>

                        <x-field label="Role" for="role_ro">
                            <input id="role_ro" type="text" value="{{ $user->role }}" class="input h-11" disabled>
                        </x-field>
                    </div>

                    {{-- Muncul hanya bila email diganti --}}
                    <div x-show="changed || @js($errors->has('email_password'))" x-cloak x-transition
                         class="rounded-md border border-hazard-500/60 bg-warn-50 p-4">
                        <p class="flex items-start gap-2 text-sm text-warn-700">
                            <x-icon name="info" :size="18" class="mt-0.5 shrink-0" />
                            Anda mengganti email. Masukkan kata sandi saat ini untuk mengonfirmasi.
                        </p>
                        <x-field label="Kata sandi saat ini" name="email_password" class="mt-3">
                            <input id="email_password" name="email_password" type="password" autocomplete="current-password"
                                   class="input h-11 bg-white @error('email_password') input-error @enderror">
                        </x-field>
                    </div>

                    <div class="flex justify-end pt-1">
                        <x-button type="submit" loading="saving">Simpan perubahan</x-button>
                    </div>
                </form>
            </x-panel>

            <x-panel title="Ubah kata sandi" description="Gunakan minimal 8 karakter dengan kombinasi huruf dan angka.">
                <form method="POST" action="{{ route('profile.password') }}" class="space-y-4 p-5" novalidate
                      x-data="{
                          next: '', confirm: '', show: false, saving: false,
                          get mismatch() { return this.confirm.length > 0 && this.confirm !== this.next },
                      }"
                      x-on:submit="saving = true">
                    @csrf
                    @method('PUT')

                    <x-field label="Kata sandi saat ini" name="current_password" required>
                        <input id="current_password" name="current_password" x-bind:type="show ? 'text' : 'password'" autocomplete="current-password"
                               class="input h-11 @error('current_password') input-error @enderror">
                    </x-field>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-field label="Kata sandi baru" name="password" required>
                            <input id="password" name="password" x-model="next" x-bind:type="show ? 'text' : 'password'" autocomplete="new-password"
                                   class="input h-11 @error('password') input-error @enderror">
                        </x-field>
                        <x-field label="Ulangi kata sandi baru" name="password_confirmation" required>
                            <input id="password_confirmation" name="password_confirmation" x-model="confirm" x-bind:type="show ? 'text' : 'password'" autocomplete="new-password"
                                   class="input h-11" x-bind:class="mismatch && 'input-error'">
                            <p class="error-text" x-show="mismatch" x-cloak>Kata sandi belum sama.</p>
                        </x-field>
                    </div>

                    <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
                        <label class="flex items-center gap-2.5 text-sm">
                            <input type="checkbox" class="check" x-model="show"> Tampilkan kata sandi
                        </label>
                        <x-button type="submit" loading="saving" x-bind:disabled="mismatch">Ubah kata sandi</x-button>
                    </div>
                </form>
            </x-panel>

        </div>
    </div>
@endsection