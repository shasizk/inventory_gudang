{{-- $movements : {id, at, item_name, serial, type ('in'|'out'), qty, stock_after, user, role} --}}
@extends('layouts.app')
@section('title', 'Riwayat barang')
@section('section', 'History')

@section('content')
    @php
        // Pengaman: bila controller lupa mengirim variabel, halaman tetap tampil dengan data kosong (bukan error 500)
        $__missing = array_values(array_diff(['movements'], array_keys(get_defined_vars())));
        $movements ??= collect();
    @endphp
    <x-dev-missing :vars="$__missing" />
    <x-page-header title="Riwayat IN/OUT barang" description="Semua barang masuk dan keluar dalam satu urutan waktu." />

    <x-table :rows="$movements" caption="Riwayat pergerakan barang" scroll empty-icon="history"
                  empty-title="Belum ada pergerakan barang" empty-text="Riwayat muncul setelah ada barang masuk atau barang keluar yang tercatat.">
        <x-slot:toolbar>
            <x-filter-bar placeholder="Cari barang atau serial number">
                <select name="type" class="input w-auto" aria-label="Jenis">
                    <option value="">IN & OUT</option>
                    <option value="in" @selected(request('type') === 'in')>IN (masuk)</option>
                    <option value="out" @selected(request('type') === 'out')>OUT (keluar)</option>
                </select>
                <input type="date" name="from" value="{{ request('from') }}" class="input w-auto" aria-label="Dari tanggal">
                <input type="date" name="to" value="{{ request('to') }}" class="input w-auto" aria-label="Sampai tanggal">
            </x-filter-bar>
        </x-slot:toolbar>
        <x-slot:head>
            <tr>
                <th>Waktu</th>
                <th>Jenis</th>
                <th>Barang</th>
                <th>Serial number</th>
                <th class="text-right">Jumlah</th>
                <th class="text-right">Stok setelah</th>
                <th>Oleh</th>
            </tr>
        </x-slot:head>
        @foreach ($movements as $m)
            <tr>
                <td><x-cell.datetime :value="$m->at" /></td>
                <td><x-status-badge :status="$m->type" /></td>
                <td><x-cell.text :value="$m->item_name" max="28ch" strong /></td>
                <td><x-cell.code :value="$m->serial" /></td>
                <td @class(['num text-right font-semibold', 'text-in-700' => $m->type === 'in', 'text-out-700' => $m->type === 'out'])>{{ $m->type === 'in' ? '+' : '−' }}{{ (int) $m->qty }}</td>
                <td class="num text-right">{{ (int) $m->stock_after }}</td>
                <td><x-cell.person :name="$m->user" :role="$m->role" /></td>
            </tr>
        @endforeach
    </x-table>
@endsection
