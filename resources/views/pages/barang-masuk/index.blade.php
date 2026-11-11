{{--
    $incoming   : {id, date, item_name, serial, category, category_id, qty, supplier, user}
    $categories : {id, code, name}
    $suppliers  : array nama supplier (untuk saran isian)
    Hak akses   : hanya Owner yang boleh mencatat barang masuk ($isOwner).
--}}
@extends('layouts.app')
@section('title', 'Barang masuk')
@section('section', 'Barang masuk')

@section('content')
    @php
        // Pengaman: bila controller lupa mengirim variabel, halaman tetap tampil dengan data kosong (bukan error 500)
        $__missing = array_values(array_diff(['incoming', 'categories', 'suppliers'], array_keys(get_defined_vars())));
        $incoming ??= collect();
        $categories ??= collect();
        $suppliers ??= [];
    @endphp
    <x-dev-missing :vars="$__missing" />
    <x-page-header title="Barang masuk" description="Penerimaan barang ke gudang. Hanya akun Owner yang dapat mencatat.">
        @if ($isOwner)
            <x-slot:actions>
                <x-button variant="secondary" icon="plus" x-on:click="$dispatch('open-modal', { name: 'barang-masuk-form', mode: 'create' })">Input manual</x-button>
            </x-slot:actions>
        @endif
    </x-page-header>

    @if ($isOwner)
        {{-- Hasil scan Owner membuka form dengan data terisi --}}
        <div x-data x-on:scan-result.window="$event.detail.mode === 'in' && $dispatch('open-modal', { name: 'barang-masuk-form', mode: 'create', data: $event.detail.prefill })">
            <x-scan-station mode="in" :endpoint="route('ajax.barang.lookup')" class="mb-6" />
        </div>
    @else
        <div class="mb-6 flex items-start gap-3 rounded-[10px] border border-in-600/25 bg-in-50 px-4 py-3.5 text-in-700" role="note">
            <x-icon name="info" class="mt-0.5" />
            <div>
                <p class="font-semibold">Akun Anda hanya dapat melihat data barang masuk</p>
                <p class="text-[15px]">Pencatatan barang masuk dilakukan oleh Owner. Barang yang Anda scan otomatis tercatat sebagai barang keluar.</p>
                <a href="{{ route('barang-keluar.index') }}#scan" class="mt-1 inline-block font-semibold underline underline-offset-2">Buka scan barang keluar</a>
            </div>
        </div>
    @endif

    <x-table :rows="$incoming" caption="Data barang masuk" empty-icon="arrow-in"
                  empty-title="Belum ada barang masuk" empty-text="Scan barcode barang yang datang untuk mencatat penerimaan pertama.">
        <x-slot:toolbar>
            <x-filter-bar placeholder="Cari nama barang, serial number, atau supplier">
                <select name="category" class="input w-auto" aria-label="Kategori">
                    <option value="">Semua kategori</option>
                    @foreach ($categories as $c)
                        <option value="{{ $c->id }}" @selected((string) request('category') === (string) $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" value="{{ request('from') }}" class="input w-auto" aria-label="Dari tanggal">
                <input type="date" name="to" value="{{ request('to') }}" class="input w-auto" aria-label="Sampai tanggal">
            </x-filter-bar>
        </x-slot:toolbar>
        <x-slot:head>
            <tr>
                <th>Tanggal</th>
                <th>Barang</th>
                <th>Serial number</th>
                <th>Kategori</th>
                <th class="text-right">Jumlah</th>
                <th>Supplier</th>
                <th>Dicatat oleh</th>
                @if ($isOwner)<th class="w-px"><span class="sr-only">Aksi</span></th>@endif
            </tr>
        </x-slot:head>

        @foreach ($incoming as $row)
            <tr>
                <td><x-cell.datetime :value="$row->date" /></td>
                <td><x-cell.text :value="$row->item_name" max="28ch" strong /></td>
                <td><x-cell.code :value="$row->serial" /></td>
                <td><x-cell.text :value="$row->category" max="18ch" class="text-ink-500" /></td>
                <td class="num text-right font-semibold whitespace-nowrap text-in-700">+{{ (int) $row->qty }}</td>
                <td><x-cell.text :value="$row->supplier" max="22ch" /></td>
                <td><x-cell.text :value="$row->user" max="18ch" class="text-ink-500" /></td>
                @if ($isOwner)
                    <td>
                        <div class="flex justify-end gap-1">
                            <button type="button" class="btn btn-ghost btn-icon" aria-label="Ubah {{ $row->item_name }}"
                                    x-on:click="$dispatch('open-modal', { name: 'barang-masuk-form', mode: 'edit', data: @js(['id' => $row->id, 'name' => $row->item_name, 'serial' => $row->serial, 'qty' => $row->qty, 'category_id' => $row->category_id, 'supplier' => $row->supplier]) })">
                                <x-icon name="edit" :size="18" />
                            </button>
                            <button type="button" class="btn btn-ghost btn-icon hover:bg-bad-50! hover:text-bad-700!" aria-label="Hapus {{ $row->item_name }}"
                                    x-on:click="$dispatch('confirm-delete', { url: @js(route('barang-masuk.destroy', $row->id)), label: @js($row->item_name . ' (' . $row->serial . ')'), note: @js('Stok barang ini akan berkurang sebanyak ' . (int) $row->qty . ' unit.') })">
                                <x-icon name="trash" :size="18" />
                            </button>
                        </div>
                    </td>
                @endif
            </tr>
        @endforeach
    </x-table>
@endsection

@if ($isOwner)
    @push('modals')
        <x-modal name="barang-masuk-form" size="lg">
            <form method="POST" x-bind:action="action" x-on:submit="guard($event)"
                  x-data="crudForm({
                      name: 'barang-masuk-form',
                      storeUrl: @js(route('barang-masuk.store')),
                      updateUrl: @js(route('barang-masuk.update', '__ID__')),
                      defaults: { name: '', serial: '', qty: 1, category_id: '', supplier: '' },
                      old: @js(old() ?: null),
                      reopen: @js($errors->any()),
                  })"
                  x-on:open-modal.window="open($event.detail); $event.detail?.name === 'barang-masuk-form' && setTimeout(() => form.serial ? $refs.qty?.focus() : $refs.serial?.focus(), 80)">
                @csrf
                <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>
                <input type="hidden" name="_editing" x-bind:value="editing ?? ''">

                <header class="modal-head">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="modal-title" x-text="editing ? 'Ubah barang masuk' : 'Catat barang masuk'"></h2>
                            <x-status-badge status="in" />
                        </div>
                        <p class="mt-1 text-sm text-ink-500" x-show="!editing && form.serial">Serial number dari hasil scan. Periksa data lalu simpan.</p>
                    </div>
                    <x-modal-close name="barang-masuk-form" />
                </header>

                <div class="modal-body">
                    <x-field label="Serial number" name="serial" hint="Klik kolom lalu scan barcode, atau ketik manual. Enter memindahkan kursor ke Jumlah." required>
                        <div class="scan-field">
                            <x-icon name="barcode" :size="18" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-ink-400" />
                            <input id="serial" name="serial" x-model="form.serial" x-ref="serial" required autocomplete="off" spellcheck="false"
                                   x-on:keydown.enter.prevent="form.serial = form.serial.trim().toUpperCase(); $refs.qty.focus()"
                                   class="input code h-11 pl-9 text-[15px] uppercase @error('serial') input-error @enderror" placeholder="SN-RTR-261021-A7K2">
                            <span class="scan-laser" aria-hidden="true"></span>
                        </div>
                    </x-field>

                    <x-field label="Nama barang" name="name" required>
                        <input id="name" name="name" x-model="form.name" required maxlength="120" class="input @error('name') input-error @enderror" placeholder="Router MikroTik hEX RB750Gr3">
                    </x-field>

                    <div class="grid gap-4 sm:grid-cols-[120px_1fr]">
                        <x-field label="Jumlah" name="qty" required>
                            <input id="qty" name="qty" type="number" min="1" x-model.number="form.qty" x-ref="qty" required class="input num @error('qty') input-error @enderror">
                        </x-field>
                        <x-field label="Kategori" name="category_id" required>
                            <select id="category_id" name="category_id" x-model="form.category_id" required class="input @error('category_id') input-error @enderror">
                                <option value="">Pilih kategori</option>
                                @foreach ($categories as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }}</option>
                                @endforeach
                            </select>
                        </x-field>
                    </div>

                    <x-field label="Supplier" name="supplier" hint="Ketik untuk memilih supplier yang pernah dipakai, atau isi nama baru." required>
                        <input id="supplier" name="supplier" list="supplier-list" x-model="form.supplier" required maxlength="120" class="input @error('supplier') input-error @enderror" placeholder="PT Citra Jaringan">
                        <datalist id="supplier-list">
                            @foreach ($suppliers as $s)<option value="{{ $s }}"></option>@endforeach
                        </datalist>
                    </x-field>
                </div>

                <footer class="modal-foot">
                    <x-button variant="secondary" x-on:click="$dispatch('close-modal', 'barang-masuk-form')">Batal</x-button>
                    <x-button type="submit" loading="saving"><span x-text="editing ? 'Simpan perubahan' : 'Simpan barang masuk'"></span></x-button>
                </footer>
            </form>
        </x-modal>
    @endpush
@endif
