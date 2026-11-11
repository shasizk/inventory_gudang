{{--
    $cards : {id, uid, name, role, active (bool), created_at, last_used_at (Carbon|null)}
    Registrasi kartu langsung dari web: tidak perlu flashing ulang ESP32.
--}}
@extends('layouts.app')
@section('title', 'Kartu RFID')
@section('section', 'User management')

@section('content')
    @php
        // Pengaman: bila controller lupa mengirim variabel, halaman tetap tampil dengan data kosong (bukan error 500)
        $__missing = array_values(array_diff(['cards'], array_keys(get_defined_vars())));
        $cards ??= collect();
    @endphp
    <x-dev-missing :vars="$__missing" />
    @if (request()->boolean('baru'))
        <div x-data x-init="$nextTick(() => $dispatch('open-modal', { name: 'card-form', mode: 'create' }))"></div>
    @endif
    <x-page-header title="Kartu RFID" description="Kartu yang terdaftar langsung bisa dipakai di pintu, tanpa mengubah program ESP32.">
        <x-slot:actions>
            <x-button icon="plus" x-on:click="$dispatch('open-modal', { name: 'card-form', mode: 'create' })">Daftarkan kartu</x-button>
        </x-slot:actions>
    </x-page-header>

    <x-table :rows="$cards" empty-icon="id-card"
                  empty-title="Belum ada kartu terdaftar" empty-text="Daftarkan kartu Owner terlebih dahulu, karena Teknisi dan NOC membutuhkan pendamping Admin atau Owner untuk membuka pintu.">
        <x-slot:toolbar>
            <x-filter-bar placeholder="Cari UID atau nama pemilik">
                <select name="role" class="input w-auto" aria-label="Peran">
                    <option value="">Semua peran</option>
                    @foreach (['owner' => 'Owner', 'admin' => 'Admin', 'teknisi' => 'Teknisi', 'noc' => 'NOC'] as $v => $l)
                        <option value="{{ $v }}" @selected(request('role') === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </x-filter-bar>
        </x-slot:toolbar>
        <x-slot:head>
            <tr>
                <th>UID kartu</th>
                <th>Pemilik</th>
                <th>Peran</th>
                <th>Cara akses</th>
                <th>Status</th>
                <th>Terakhir dipakai</th>
                <th class="w-px"><span class="sr-only">Aksi</span></th>
            </tr>
        </x-slot:head>

        @foreach ($cards as $card)
            @php $active = filter_var($card->active, FILTER_VALIDATE_BOOL); // "0"/"1"/0/1/true/false @endphp
            <tr @class(['text-ink-500' => ! $active])>
                <td><x-cell.code :value="$card->uid" group /></td>
                <td><x-cell.text :value="$card->name" max="24ch" strong /></td>
                <td><x-status-badge :status="$card->role" /></td>
                <td class="text-sm whitespace-nowrap">{{ in_array(strtolower((string) $card->role), ['owner', 'admin'], true) ? 'Langsung' : 'Wajib didampingi Admin/Owner' }}</td>
                <td>
                    @if ($active)
                        <x-status-badge status="success" :icon="false">Aktif</x-status-badge>
                    @else
                        <x-status-badge status="neutral">Diblokir</x-status-badge>
                    @endif
                </td>
                <td><x-cell.datetime :value="$card->last_used_at" mode="inline" empty="Belum pernah" /></td>
                <td>
                    <div class="flex justify-end gap-1">
                        <button type="button" class="btn btn-ghost btn-icon" aria-label="Ubah kartu {{ $card->name }}"
                                x-on:click="$dispatch('open-modal', { name: 'card-form', mode: 'edit', data: @js(['id' => $card->id, 'uid' => $card->uid, 'name' => $card->name, 'role' => $card->role, 'active' => $active]) })">
                            <x-icon name="edit" :size="18" />
                        </button>
                        <button type="button" class="btn btn-ghost btn-icon hover:bg-bad-50! hover:text-bad-700!" aria-label="Hapus kartu {{ $card->name }}"
                                x-on:click="$dispatch('confirm-delete', { url: @js(route('cards.destroy', $card->id)), label: @js('Kartu ' . $card->name), note: 'Kartu langsung tidak bisa membuka pintu. Untuk menonaktifkan sementara, pilih Ubah lalu blokir kartu.' })">
                            <x-icon name="trash" :size="18" />
                        </button>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-table>
@endsection

@push('modals')
    <x-modal name="card-form" size="md">
        <form method="POST" x-bind:action="action" x-on:submit="guard($event)"
              x-data="crudForm({
                  name: 'card-form',
                  storeUrl: @js(route('cards.store')),
                  updateUrl: @js(route('cards.update', '__ID__')),
                  defaults: { uid: '', name: '', role: 'teknisi', active: true },
                  old: @js(old() ?: null),
                  reopen: @js($errors->any()),
              })"
              x-on:open-modal.window="open($event.detail)"
              x-on:rfid-captured="form.uid = $event.detail">
            @csrf
            <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>
            <input type="hidden" name="_editing" x-bind:value="editing ?? ''">

            <header class="modal-head">
                <h2 class="modal-title" x-text="editing ? 'Ubah kartu RFID' : 'Daftarkan kartu RFID'"></h2>
                <x-modal-close name="card-form" />
            </header>

            <div class="modal-body">
                {{-- Scan UID: tunggu tap dari reader ESP32, atau reader USB mengetik langsung ke kolom --}}
                <div x-data="rfidCapture({ endpoint: @js(route('ajax.rfid.pending')) })" x-on:close-modal.window="stop()">
                    <x-field label="UID kartu" name="uid" required>
                        <div class="flex gap-2">
                            <input id="uid" name="uid" x-model="form.uid" required autocomplete="off" spellcheck="false"
                                   x-on:input="form.uid = form.uid.toUpperCase().replace(/[^0-9A-F]/g, '')" x-on:keydown.enter.prevent="$refs.owner.focus()"
                                   class="input code h-11 min-w-0 flex-1 text-base tracking-wider uppercase placeholder:font-sans placeholder:normal-case placeholder:tracking-normal @error('uid') input-error @enderror" placeholder="Tap kartu atau ketik UID">
                            <button type="button" class="btn btn-scan h-11 shrink-0" x-on:click="waiting ? stop() : start()">
                                <span x-show="!waiting"><x-icon name="rfid" :size="18" /></span>
                                <span x-show="waiting" class="spinner" x-cloak></span>
                                <span x-text="waiting ? 'Batal' : 'Tunggu tap kartu'"></span>
                            </button>
                        </div>
                    </x-field>
                    <div x-show="waiting" x-cloak class="mt-2 flex items-center gap-3 rounded-md bg-hazard-300/25 px-3 py-2.5 text-sm" role="status">
                        <span class="relative flex size-3"><span class="absolute inset-0 animate-ping rounded-full bg-hazard-500"></span><span class="relative size-3 rounded-full bg-hazard-500"></span></span>
                        <span>Tempelkan kartu baru ke reader pintu gudang. Menunggu <b class="num" x-text="remaining"></b> detik.</span>
                    </div>
                    <p class="hint" x-show="!waiting && form.uid" x-text="'Format tampilan: ' + (form.uid.match(/.{1,2}/g) ?? []).join(' ')"></p>
                </div>

                <x-field label="Nama pemilik kartu" name="name" required>
                    <input id="name" name="name" x-model="form.name" x-ref="owner" required maxlength="80" class="input @error('name') input-error @enderror" placeholder="Rizky Pratama">
                </x-field>

                <fieldset>
                    <legend class="label">Peran <span class="text-bad-600" aria-hidden="true">*</span></legend>
                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                        @foreach (['owner' => 'Owner', 'admin' => 'Admin', 'teknisi' => 'Teknisi', 'noc' => 'NOC'] as $v => $l)
                            <label class="flex h-10 cursor-pointer items-center justify-center rounded-md border text-[15px] font-semibold transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-in-600"
                                   x-bind:class="form.role === '{{ $v }}' ? 'border-ink-900 bg-ink-900 text-white' : 'border-concrete-300 bg-white hover:border-ink-300'">
                                <input type="radio" name="role" value="{{ $v }}" x-model="form.role" class="sr-only">{{ $l }}
                            </label>
                        @endforeach
                    </div>
                    <p class="hint" x-text="['owner', 'admin'].includes(form.role)
                        ? 'Kartu ini membuka pintu langsung dan bisa mendampingi verifikasi Teknisi/NOC.'
                        : 'Pintu baru terbuka setelah kartu ini di-tap, lalu disusul tap kartu Admin atau Owner.'"></p>
                    @error('role')<p class="error-text">{{ $message }}</p>@enderror
                </fieldset>

                <label class="flex items-center justify-between gap-4 rounded-md border border-concrete-300 px-3.5 py-3">
                    <span>
                        <span class="block font-semibold">Kartu aktif</span>
                        <span class="block text-sm text-ink-500">Matikan untuk memblokir kartu hilang tanpa menghapus riwayatnya.</span>
                    </span>
                    <input type="hidden" name="active" value="0">
                    <input type="checkbox" name="active" value="1" x-model="form.active" class="check size-5">
                </label>
            </div>

            <footer class="modal-foot">
                <x-button variant="secondary" x-on:click="$dispatch('close-modal', 'card-form')">Batal</x-button>
                <x-button type="submit" loading="saving"><span x-text="editing ? 'Simpan perubahan' : 'Daftarkan kartu'"></span></x-button>
            </footer>
        </form>
    </x-modal>
@endpush
