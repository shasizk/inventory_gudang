{{--
    $logs : {id, at, name, role, door ('gudang'|'ict'), direction ('in'|'out'),
             method ('rfid'|'two_step'), companion (nama pendamping|null), result ('granted'|'denied'), reason|null}
--}}
@extends('layouts.app')
@section('title', 'Riwayat pintu')
@section('section', 'History')

@section('content')
    @php
        // Pengaman: bila controller lupa mengirim variabel, halaman tetap tampil dengan data kosong (bukan error 500)
        $__missing = array_values(array_diff(['logs'], array_keys(get_defined_vars())));
        $logs ??= collect();
    @endphp
    <x-dev-missing :vars="$__missing" />
    <x-page-header title="Riwayat IN/OUT pintu" description="Semua tap kartu di pintu gudang dan ruang ICT, termasuk yang ditolak." />

    <x-table :rows="$logs" caption="Riwayat akses pintu" scroll empty-icon="door"
                  empty-title="Belum ada riwayat akses" empty-text="Setiap tap kartu RFID di pintu akan tercatat di sini.">
        <x-slot:toolbar>
            <x-filter-bar placeholder="Cari nama atau UID">
                <select name="door" class="input w-auto" aria-label="Pintu">
                    <option value="">Semua pintu</option>
                    <option value="gudang" @selected(request('door') === 'gudang')>Pintu gudang</option>
                    <option value="ict" @selected(request('door') === 'ict')>Ruang ICT</option>
                </select>
                <select name="direction" class="input w-auto" aria-label="Arah">
                    <option value="">IN & OUT</option>
                    <option value="in" @selected(request('direction') === 'in')>IN</option>
                    <option value="out" @selected(request('direction') === 'out')>OUT</option>
                </select>
                <select name="result" class="input w-auto" aria-label="Hasil">
                    <option value="">Semua hasil</option>
                    <option value="granted" @selected(request('result') === 'granted')>Diizinkan</option>
                    <option value="denied" @selected(request('result') === 'denied')>Ditolak</option>
                </select>
                <input type="date" name="date" value="{{ request('date') }}" class="input w-auto" aria-label="Tanggal">
            </x-filter-bar>
        </x-slot:toolbar>
        <x-slot:head>
            <tr>
                <th>Waktu</th>
                <th>Nama</th>
                <th>Pintu</th>
                <th>Arah</th>
                <th>Cara akses</th>
                <th>Hasil</th>
            </tr>
        </x-slot:head>
        @foreach ($logs as $log)
            <tr @class(['bg-bad-50/50' => $log->result === 'denied'])>
                <td><x-cell.datetime :value="$log->at" seconds /></td>
                <td><x-cell.person :name="$log->name" :role="$log->role" /></td>
                <td><x-status-badge :status="$log->door" /></td>
                <td><x-status-badge :status="$log->direction" /></td>
                <td class="text-sm">
                    @if (($log->method ?? null) === 'two_step')
                        Verifikasi 2 langkah
                        <span class="block text-ink-500">{{ $log->companion ? 'Didampingi ' . $log->companion : 'Tanpa pendamping' }}</span>
                    @else
                        Kartu RFID langsung
                    @endif
                </td>
                <td>
                    <x-status-badge :status="$log->result" />
                    @if ($log->result === 'denied' && $log->reason)
                        <x-cell.text :value="$log->reason" max="30ch" lines="2" class="mt-1 text-[13px] text-bad-700" />
                    @endif
                </td>
            </tr>
        @endforeach
    </x-table>
@endsection
