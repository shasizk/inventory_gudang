{{-- $audits : {id, at, user, role, module, action ('create'|'update'|'delete'|'login'|'scan'|'door'|'export'), description, ip} --}}
@extends('layouts.app')
@section('title', 'Audit log')
@section('section', 'History')

@section('content')
    @php
        // Pengaman: bila controller lupa mengirim variabel, halaman tetap tampil dengan data kosong (bukan error 500)
        $__missing = array_values(array_diff(['audits'], array_keys(get_defined_vars())));
        $audits ??= collect();
    @endphp
    <x-dev-missing :vars="$__missing" />
    @php
        $actions = [
            'door' => ['Pintu', 'gudang'],
            'forced_open' => ['Pintu dibuka paksa', 'danger'],
        ];
        $modules = ['Smart Lock'];
    @endphp

    <x-page-header title="Audit log" description="Log aktual akses pintu gudang dan alarm, termasuk kejadian pintu dibuka paksa." />

    <x-role-gate :allow="['owner', 'admin']" title="Audit log khusus Owner dan Admin"
                 text="Log aktivitas Smart Lock berasal dari perangkat pintu. Alamat IP tidak tersedia untuk catatan perangkat.">

    <x-table :rows="$audits" caption="Audit log sistem" scroll empty-icon="shield"
              empty-title="Audit log masih kosong" empty-text="Aktivitas pintu gudang dan alarm akan tampil setelah perangkat mengirim log.">
        <x-slot:toolbar>
            <x-filter-bar placeholder="Cari pengguna atau keterangan">
                <select name="module" class="input w-auto" aria-label="Modul">
                    <option value="">Semua modul</option>
                    @foreach ($modules as $mod)
                        <option value="{{ $mod }}" @selected(request('module') === $mod)>{{ $mod }}</option>
                    @endforeach
                </select>
                <select name="action" class="input w-auto" aria-label="Aksi">
                    <option value="">Semua aksi</option>
                    @foreach ($actions as $key => [$label])
                        <option value="{{ $key }}" @selected(request('action') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="date" name="date" value="{{ request('date') }}" class="input w-auto" aria-label="Tanggal">
            </x-filter-bar>
        </x-slot:toolbar>
        <x-slot:head>
            <tr>
                <th>Waktu</th>
                <th>Pengguna</th>
                <th>Modul</th>
                <th>Aksi</th>
                <th>Keterangan</th>
                <th>Alamat IP</th>
            </tr>
        </x-slot:head>
        @foreach ($audits as $a)
            @php [$label, $tone] = $actions[$a->action] ?? [ucfirst($a->action), 'neutral']; @endphp
            <tr>
                <td><x-cell.datetime :value="$a->at" seconds /></td>
                <td><x-cell.person :name="$a->user" :role="$a->role" /></td>
                <td class="whitespace-nowrap text-ink-700">{{ $a->module }}</td>
                <td><x-status-badge :status="$tone" :icon="false">{{ $label }}</x-status-badge></td>
                <td><x-cell.text :value="$a->description" max="44ch" lines="2" class="text-ink-700" /></td>
                <td><x-cell.code :value="$a->ip ?? 'Perangkat'" class="text-ink-500" /></td>
            </tr>
        @endforeach
    </x-table>
    </x-role-gate>
@endsection
