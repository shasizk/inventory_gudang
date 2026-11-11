{{--
    $serials    : paginator SerialNumber {id, sn_no, serial, item_name, category (relasi), source ('pabrik'|'sistem'), status ('gudang'|'keluar'), created_at}
    $categories : {id, code, name}  (untuk form)
--}}
@extends('layouts.app')
@section('title', 'Serial number')
@section('section', 'Barang masuk')

@section('content')
    @php
        // Pengaman: bila controller lupa mengirim variabel, halaman tetap tampil dengan data kosong (bukan error 500)
        $__missing = array_values(array_diff(['serials', 'categories'], array_keys(get_defined_vars())));
        $serials ??= collect();
        $categories ??= collect();
    @endphp
    <x-dev-missing :vars="$__missing" />
    <x-page-header title="Serial number" description="Nomor unik per unit barang. Cetak label QR-nya dan tempel di barang yang belum punya serial number.">
        @if ($canManage)
            <x-slot:actions>
                <x-button icon="barcode" x-on:click="$dispatch('open-modal', { name: 'serial-form', mode: 'create' })">Tambah serial number</x-button>
            </x-slot:actions>
        @endif
    </x-page-header>

    {{-- Muncul sekali setelah simpan: unduh PDF label untuk unit yang baru dibuat --}}
    @if (session('label_ids'))
        <div class="panel mb-4 flex flex-wrap items-center justify-between gap-3 px-4 py-3">
            <p class="text-sm"><strong>{{ count(session('label_ids')) }} label QR</strong> siap dicetak. Tempel di barang sebelum disimpan di gudang.</p>
            <x-button icon="download" :href="route('serial.label', ['ids' => session('label_ids')])">Unduh PDF label</x-button>
        </div>
    @endif

    <x-table :rows="$serials" empty-icon="barcode"
                  empty-title="Belum ada serial number" empty-text="Tambah serial number untuk barang yang belum punya label, lalu unduh PDF label QR-nya.">
        <x-slot:toolbar>
            <x-filter-bar placeholder="Cari serial number atau nama barang">
                <select name="source" class="input w-auto" aria-label="Sumber">
                    <option value="">Semua sumber</option>
                    <option value="pabrik" @selected(request('source') === 'pabrik')>Bawaan pabrik</option>
                    <option value="sistem" @selected(request('source') === 'sistem')>Generate sistem</option>
                </select>
                <select name="status" class="input w-auto" aria-label="Status unit">
                    <option value="">Semua status</option>
                    <option value="gudang" @selected(request('status') === 'gudang')>Di gudang</option>
                    <option value="keluar" @selected(request('status') === 'keluar')>Sudah keluar</option>
                </select>
                @if (count($serials) > 0)
                    <x-slot:actions>
                        <x-button variant="secondary" icon="download" :href="route('serial.label.all', request()->except(['page', 'as']))">Unduh semua label</x-button>
                    </x-slot:actions>
                @endif
            </x-filter-bar>
        </x-slot:toolbar>
        <x-slot:head>
            <tr>
                <th>No. SN</th>
                <th>Serial number</th>
                <th>Barang</th>
                <th>Kategori</th>
                <th>Sumber</th>
                <th>Status</th>
                <th>Dibuat</th>
                <th class="w-px"><span class="sr-only">Aksi</span></th>
            </tr>
        </x-slot:head>

        @foreach ($serials as $sn)
            <tr>
                <td><x-cell.code :value="$sn->sn_no" /></td>
                <td><x-cell.code :value="$sn->serial" /></td>
                <td><x-cell.text :value="$sn->item_name" max="30ch" strong /></td>
                <td><x-cell.text :value="$sn->category?->name ?? '-'" max="20ch" class="text-ink-500" /></td>
                <td><x-status-badge status="neutral">{{ $sn->source === 'sistem' ? 'Generate sistem' : 'Bawaan pabrik' }}</x-status-badge></td>
                <td>
                    @if ($sn->status === 'gudang')
                        <x-status-badge status="success" :icon="false">Di gudang</x-status-badge>
                    @else
                        <x-status-badge status="out">Keluar</x-status-badge>
                    @endif
                </td>
                <td class="text-ink-500"><x-cell.datetime :value="$sn->created_at" date-only /></td>
                <td>
                    <div class="flex justify-end gap-1">
                        <x-button variant="ghost" size="sm" icon="download" :href="route('serial.label', ['ids' => [$sn->id]])">Label PDF</x-button>
                        @if ($canManage)
                            <button type="button" class="btn btn-ghost btn-icon hover:bg-bad-50! hover:text-bad-700!" aria-label="Hapus {{ $sn->serial }}"
                                    x-on:click="$dispatch('confirm-delete', { url: @js(route('serial.destroy', $sn->id)), label: @js($sn->serial), note: @js($sn->status === 'keluar' ? 'Unit ini sudah keluar gudang dan tidak bisa dihapus.' : null) })">
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
    <x-modal name="serial-form" size="md">
        <form method="POST" x-bind:action="action" x-on:submit="guard($event)"
              x-data="crudForm({
                  name: 'serial-form',
                  storeUrl: @js(route('serial.store')),
                  updateUrl: @js(route('serial.store')),
                  defaults: { category_id: '', item_name: '', serial: '', count: 1 },
                  old: @js(old() ?: null),
                  reopen: @js($errors->any()),
              })"
              x-on:open-modal.window="open($event.detail)">
            @csrf
            <input type="hidden" name="_editing" value="">

            <header class="modal-head">
                <div>
                    <h2 class="modal-title">Tambah serial number</h2>
                    <p class="mt-1 text-sm text-ink-500">Tersimpan sebagai barang masuk. Nomor SN dibuat otomatis oleh sistem.</p>
                </div>
                <x-modal-close name="serial-form" />
            </header>
            <div class="modal-body">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="SN_NO" name="code" hint="Dibuat otomatis oleh sistem">
                        <input id="code" x-model="form.code" readonly tabindex="-1"
                            class="input code uppercase bg-concrete-100" placeholder="Otomatis">
                    </x-field>
                    <x-field label="Kategori" name="category_id" required>
                        <select id="category_id" name="category_id" x-model="form.category_id" class="input @error('category_id') input-error @enderror" required>
                            <option value="">Pilih kategori</option>
                            @foreach ($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }})</option>
                            @endforeach
                        </select>
                    </x-field>
                    <x-field label="Nama barang" name="item_name" required>
                        <input id="item_name" name="item_name" x-model="form.item_name" maxlength="120" required class="input @error('item_name') input-error @enderror" placeholder="Switch TP-Link 8 port">
                    </x-field>
                </div>
                <div class="grid gap-4 sm:grid-cols-[1fr_130px]">
                    <x-field label="Serial number" name="serial" hint="Kosongkan agar sistem memakai nomor SN otomatis. Bila jumlah unit lebih dari 1, serial dibuat berurutan dari angka di belakangnya (A003 menjadi A003, A004, A005).">
                        <input id="serial" name="serial" x-model="form.serial" maxlength="64" autocomplete="off" class="input code @error('serial') input-error @enderror" placeholder="Serial Number">
                    </x-field>
                    <x-field label="Jumlah unit" name="count" hint="Maksimal 50 per simpan.">
                        <input id="count" name="count" type="number" min="1" max="50" x-model.number="form.count" class="input num @error('count') input-error @enderror">
                    </x-field>
                </div>
                {{-- Pratinjau rentang serial bila jumlah lebih dari 1 --}}
                <p class="hint mt-0!" x-cloak x-show="form.serial.trim() !== '' && Number(form.count) > 1"
                   x-text="(() => {
                       const serial = form.serial.trim();
                       const m = serial.match(/^(.*?)(\d+)$/);
                       if (!m || m[2].length > 15) return 'Serial harus diakhiri angka (maks. 15 digit) agar bisa dibuat berurutan.';
                       const last = m[1] + String(Number(m[2]) + Number(form.count) - 1).padStart(m[2].length, '0');
                       return form.count + ' unit akan dibuat: ' + serial + ' sampai ' + last;
                   })()"></p>
            </div>
            <footer class="modal-foot">
                <x-button variant="secondary" x-on:click="$dispatch('close-modal', 'serial-form')">Batal</x-button>
                <x-button type="submit" loading="saving">Simpan serial number</x-button>
            </footer>
        </form>
    </x-modal>
@endpush