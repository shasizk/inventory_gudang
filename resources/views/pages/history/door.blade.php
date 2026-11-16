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

    <div
        x-data="doorHistory({
            endpoint: @js(route('ajax.door.logs.latest', request()->only(['q', 'search', 'door', 'direction', 'result', 'date']))),
            after: @js($latestLogId),
            updatedAfter: @js(now()->format('Y-m-d H:i:s')),
            enabled: @js((int) request('page', 1) === 1),
        })"
    >
    <p class="mb-3 text-xs text-ink-500" aria-live="polite">
        Pembaruan otomatis setiap 2 detik{{ (int) request('page', 1) === 1 ? '.' : ' tersedia di halaman pertama.' }}
    </p>
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
            @include('pages.history.partials.door-log-row', ['log' => $log])
        @endforeach
    </x-table>
    </div>
@endsection
