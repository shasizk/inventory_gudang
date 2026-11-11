{{-- $users : {id, username, email, role, last_login_at (Carbon|null)} --}}
@extends('layouts.app')
@section('title', 'Akun web')
@section('section', 'User management')

@section('content')
    @php
        // Pengaman: bila controller lupa mengirim variabel, halaman tetap tampil dengan data kosong (bukan error 500)
        $__missing = array_values(array_diff(['users'], array_keys(get_defined_vars())));
        $users ??= collect();
    @endphp
    <x-dev-missing :vars="$__missing" />
    @php
        // Owner terakhir tidak boleh dihapus/diturunkan perannya: tanpa Owner, tidak ada yang bisa mencatat barang masuk
        $rowsList = collect($users instanceof \Illuminate\Contracts\Pagination\Paginator ? $users->items() : $users);
        $owners = $rowsList->filter(fn ($u) => strtolower((string) $u->role) === 'owner');
        $lastOwnerId = $owners->count() === 1 ? $owners->first()->id : null;
        $me = auth()->id();
    @endphp

    <x-page-header title="Akun web" description="Akun untuk masuk ke website. Owner mencatat barang masuk, peran lain barang keluar.">
        <x-slot:actions>
            <x-button icon="plus" x-on:click="$dispatch('open-modal', { name: 'user-form', mode: 'create' })">Tambah akun</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table :rows="$users" empty-icon="users" empty-title="Belum ada akun lain" empty-text="Tambahkan akun untuk Admin, Teknisi, atau NOC.">
        <x-slot:toolbar>
            <x-filter-bar placeholder="Cari username atau email" />
        </x-slot:toolbar>
        <x-slot:head>
            <tr>
                <th>Username</th>
                <th>Email</th>
                <th>Peran</th>
                <th>Hak scan</th>
                <th>Terakhir masuk</th>
                <th class="w-px"><span class="sr-only">Aksi</span></th>
            </tr>
        </x-slot:head>
        @foreach ($users as $u)
            <tr>
                <td><x-cell.text :value="$u->username" max="22ch" strong /></td>
                <td><x-cell.text :value="$u->email" max="30ch" class="text-ink-700" /></td>
                <td><x-status-badge :status="$u->role" /></td>
                <td>@if (strtolower((string) $u->role) === 'owner')<x-status-badge status="in">Barang masuk</x-status-badge>@else<x-status-badge status="out">Barang keluar</x-status-badge>@endif</td>
                <td class="text-ink-500"><x-cell.datetime :value="$u->last_login_at" mode="inline" empty="Belum pernah" /></td>
                <td>
                    <div class="flex justify-end gap-1">
                        <button type="button" class="btn btn-ghost btn-icon" aria-label="Ubah akun {{ $u->username }}"
                                x-on:click="$dispatch('open-modal', { name: 'user-form', mode: 'edit', data: @js(['id' => $u->id, 'username' => $u->username, 'email' => $u->email, 'role' => $u->role]) })">
                            <x-icon name="edit" :size="18" />
                        </button>
                        @php
                            // Bandingkan sebagai teks: id dari DB bisa "1" (teks) sementara auth()->id() bernilai 1 (angka)
                            $isMe = $me !== null && (string) $me === (string) $u->id;
                            $locked = ($lastOwnerId !== null && (string) $u->id === (string) $lastOwnerId) || $isMe;
                        @endphp
                        @if ($locked)
                            <span class="btn btn-ghost btn-icon cursor-not-allowed opacity-40" tabindex="0" role="img"
                                  title="{{ $isMe ? 'Akun yang sedang dipakai tidak bisa dihapus' : 'Minimal harus ada satu akun Owner' }}"
                                  aria-label="Hapus tidak tersedia: {{ $isMe ? 'akun yang sedang dipakai' : 'ini satu-satunya akun Owner' }}">
                                <x-icon name="trash" :size="18" />
                            </span>
                        @else
                            <button type="button" class="btn btn-ghost btn-icon hover:bg-bad-50! hover:text-bad-700!" aria-label="Hapus akun {{ $u->username }}"
                                    x-on:click="$dispatch('confirm-delete', { url: @js(route('users.destroy', $u->id)), label: @js('Akun ' . $u->username) })">
                                <x-icon name="trash" :size="18" />
                            </button>
                        @endif
                    </div>
                </td>
            </tr>
        @endforeach
    </x-table>
@endsection

@push('modals')
    <x-modal name="user-form" size="md">
        <form method="POST" x-bind:action="action" x-on:submit="guard($event)"
              x-data="crudForm({
                  name: 'user-form',
                  storeUrl: @js(route('users.store')),
                  updateUrl: @js(route('users.update', '__ID__')),
                  defaults: { username: '', email: '', role: 'teknisi' },
                  old: @js(old() ? \Illuminate\Support\Arr::except(old(), ['password', 'password_confirmation']) : null),
                  reopen: @js($errors->any()),
              })"
              x-on:open-modal.window="open($event.detail)">
            @csrf
            <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>
            <input type="hidden" name="_editing" x-bind:value="editing ?? ''">

            <header class="modal-head">
                <h2 class="modal-title" x-text="editing ? 'Ubah akun' : 'Tambah akun'"></h2>
                <x-modal-close name="user-form" />
            </header>
            <div class="modal-body">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Username" name="username" required>
                        <input id="username" name="username" x-model="form.username" required maxlength="40" autocomplete="off" class="input @error('username') input-error @enderror" placeholder="rizky.teknisi">
                    </x-field>
                    <x-field label="Peran" name="role" required>
                        <select id="role" name="role" x-model="form.role" required class="input @error('role') input-error @enderror">
                            <option value="owner">Owner</option>
                            <option value="admin">Admin</option>
                            <option value="teknisi">Teknisi</option>
                            <option value="noc">NOC</option>
                        </select>
                    </x-field>
                </div>
                <x-field label="Email" name="email" required>
                    <input id="email" type="email" name="email" x-model="form.email" required class="input @error('email') input-error @enderror" placeholder="nama@perusahaan.co.id">
                </x-field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="Kata sandi" name="password">
                        <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" x-bind:required="!editing" class="input @error('password') input-error @enderror">
                    </x-field>
                    <x-field label="Ulangi kata sandi" name="password_confirmation">
                        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" x-bind:required="!editing" class="input">
                    </x-field>
                </div>
                <p class="hint mt-0!" x-text="editing ? 'Kosongkan kata sandi bila tidak ingin mengubahnya.' : 'Minimal 8 karakter.'"></p>
                <div x-show="editing && String(editing) === String(@js($lastOwnerId)) && form.role !== 'owner'" x-cloak
                     class="flex items-start gap-2.5 rounded-md bg-bad-50 px-3.5 py-3 text-sm text-bad-700" role="alert">
                    <x-icon name="alert" :size="18" class="mt-0.5" />
                    <span>Ini satu-satunya akun Owner. Tambahkan Owner lain terlebih dahulu sebelum mengubah perannya.</span>
                </div>
            </div>
            <footer class="modal-foot">
                <x-button variant="secondary" x-on:click="$dispatch('close-modal', 'user-form')">Batal</x-button>
                <x-button type="submit" loading="saving" x-bind:disabled="editing && String(editing) === String({{ \Illuminate\Support\Js::from($lastOwnerId) }}) && form.role !== 'owner'"><span x-text="editing ? 'Simpan perubahan' : 'Buat akun'"></span></x-button>
            </footer>
        </form>
    </x-modal>
@endpush
