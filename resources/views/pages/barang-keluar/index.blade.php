{{--
    $outgoing : {id, at, item_name, serial, category, qty, stock_after, user, role, source ('scanner'|'manual')}
    Scan keluar hanya untuk akun selain Owner (Admin, Teknisi, NOC).
--}}
@extends('layouts.app')
@section('title', 'Barang keluar')
@section('section', 'Barang keluar')

@section('content')
    @php
        // Pengaman: bila controller lupa mengirim variabel, halaman tetap tampil dengan data kosong (bukan error 500)
        $__missing = array_values(array_diff(['outgoing'], array_keys(get_defined_vars())));
        $outgoing ??= collect();
    @endphp
    <x-dev-missing :vars="$__missing" />
    <x-page-header title="Barang keluar" description="Barang yang keluar dari hasil scan Admin, Teknisi, dan NOC." />

    @unless ($isOwner)
        {{-- Setelah scan sukses, muat ulang tabel agar baris baru terlihat (ganti dengan fetch partial bila diinginkan) --}}
        <div x-data="{ pending: false }" x-on:scan-result.window="$event.detail.mode === 'out' && (pending = true)">
            <x-scan-station mode="out" :endpoint="route('ajax.barang.keluar.scan')" class="mb-6" />
            <div x-show="pending" x-cloak class="-mt-3 mb-6 flex flex-wrap items-center justify-between gap-3 rounded-md border border-concrete-300 bg-white px-4 py-2.5 text-sm">
                <span>Ada barang keluar baru yang belum tampil di tabel.</span>
                <button type="button" class="btn btn-secondary btn-sm" x-on:click="$dispatch('table-loading', true); location.reload()"><x-icon name="refresh" :size="16" /> Muat ulang tabel</button>
            </div>
        </div>
    @else
        <div class="mb-6 flex items-start gap-3 rounded-[10px] border border-in-600/25 bg-in-50 px-4 py-3.5 text-in-700" role="note">
            <x-icon name="info" class="mt-0.5" />
            <p class="text-[15px]"><span class="font-semibold">Akun Owner mencatat barang masuk.</span> Scan barang keluar dilakukan oleh akun Admin, Teknisi, atau NOC, lalu tercatat di tabel ini.</p>
        </div>
    @endunless

    <x-table :rows="$outgoing" caption="Mutasi barang keluar" scroll empty-icon="arrow-out"
                  empty-title="Belum ada barang keluar" empty-text="Barang yang di-scan oleh Admin, Teknisi, atau NOC akan tercatat di sini.">
        <x-slot:toolbar>
            <x-filter-bar placeholder="Cari barang, serial number, atau nama petugas">
                <select name="role" class="input w-auto" aria-label="Peran petugas">
                    <option value="">Semua petugas</option>
                    @foreach (['admin' => 'Admin', 'teknisi' => 'Teknisi', 'noc' => 'NOC'] as $value => $label)
                        <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="date" name="date" value="{{ request('date') }}" class="input w-auto" aria-label="Tanggal">
            </x-filter-bar>
        </x-slot:toolbar>
        <x-slot:head>
            <tr>
                <th>Waktu</th>
                <th>Barang</th>
                <th>Serial number</th>
                <th class="text-right">Jumlah</th>
                <th class="text-right">Sisa stok</th>
                <th>Diambil oleh</th>
                <th>Metode</th>
            </tr>
        </x-slot:head>

        @foreach ($outgoing as $row)
            <tr>
                <td><x-cell.datetime :value="$row->at" seconds /></td>
                <td><x-cell.text :value="$row->item_name" :sub="$row->category" max="28ch" strong /></td>
                <td><x-cell.code :value="$row->serial" /></td>
                <td class="num text-right font-semibold whitespace-nowrap text-out-700">−{{ (int) $row->qty }}</td>
                <td class="text-right">
                    {{-- Angka & badge satu baris, rata kanan: badge tidak lagi turun ke bawah angka --}}
                    <span class="inline-flex items-center justify-end gap-1.5 whitespace-nowrap">
                        @if ((int) $row->stock_after <= 0)<x-status-badge status="habis" :icon="false" />@endif
                        <span class="num font-semibold">{{ (int) $row->stock_after }}</span>
                    </span>
                </td>
                <td><x-cell.person :name="$row->user" :role="$row->role" /></td>
                <td class="whitespace-nowrap text-ink-500">{{ ($row->source ?? null) === 'scanner' ? 'Scanner' : 'Ketik manual' }}</td>
            </tr>
        @endforeach
    </x-table>
@endsection
