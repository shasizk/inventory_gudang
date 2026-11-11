{{--
    $attendance : {name, role, date (Carbon), first_tap (Carbon|null), last_tap (Carbon|null), door}
    Aturan rekap (dihitung backend dari log tap):
      first_tap = tap pertama hari itu  -> Absen masuk
      last_tap  = tap terakhir hari itu -> Absen keluar (null bila baru satu kali tap)
--}}
@extends('layouts.app')
@section('title', 'Absensi Teknisi & NOC')
@section('section', 'Absensi')

@section('content')
    @php
        // Pengaman: bila controller lupa mengirim variabel, halaman tetap tampil dengan data kosong (bukan error 500)
        $__missing = array_values(array_diff(['attendance'], array_keys(get_defined_vars())));
        $attendance ??= collect();
    @endphp
    <x-dev-missing :vars="$__missing" />
    <x-page-header title="Absensi Teknisi & NOC" description="Tap pertama dihitung absen masuk, tap terakhir di hari yang sama absen keluar." />

    @php
        $complete = collect($attendance instanceof \Illuminate\Contracts\Pagination\Paginator ? $attendance->items() : $attendance)->filter(fn ($a) => filled($a->last_tap ?? null))->count();
        $open = count($attendance) - $complete;
    @endphp
    <p class="mb-4 text-[15px] text-ink-700">
        <span class="num font-semibold">{{ count($attendance) }}</span> orang tercatat,
        <span class="num font-semibold text-ok-700">{{ $complete }}</span> lengkap,
        <span class="num font-semibold text-warn-700">{{ $open }}</span> belum tap keluar.
    </p>

    <x-table :rows="$attendance" empty-icon="clock"
                  empty-title="Belum ada tap kartu di tanggal ini" empty-text="Absensi muncul setelah Teknisi atau NOC menempelkan kartu di pintu gudang atau ruang ICT.">
        <x-slot:toolbar>
            <x-filter-bar placeholder="Cari nama">
                <label class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-ink-700">Tanggal</span>
                    <input type="date" name="date" value="{{ request('date', now()->toDateString()) }}" class="input w-auto">
                </label>
                <select name="role" class="input w-auto" aria-label="Peran">
                    <option value="">Teknisi & NOC</option>
                    <option value="teknisi" @selected(request('role') === 'teknisi')>Teknisi</option>
                    <option value="noc" @selected(request('role') === 'noc')>NOC</option>
                </select>
            </x-filter-bar>
        </x-slot:toolbar>
        <x-slot:head>
            <tr>
                <th>Nama</th>
                <th>Tanggal</th>
                <th>Absen masuk</th>
                <th>Absen keluar</th>
                <th class="text-right">Durasi</th>
                <th>Pintu</th>
                <th>Status</th>
            </tr>
        </x-slot:head>

        @foreach ($attendance as $a)
            <tr>
                <td><x-cell.person :name="$a->name" :role="$a->role" /></td>
                <td class="text-ink-500"><x-cell.datetime :value="$a->date" mode="long" /></td>
                <td class="font-semibold text-in-700"><x-cell.datetime :value="$a->first_tap" mode="time" /></td>
                <td @class(['font-semibold', 'text-out-700' => filled($a->last_tap)])><x-cell.datetime :value="$a->last_tap" mode="time" empty="Belum tap" /></td>
                <td class="text-right"><x-cell.duration :from="$a->first_tap" :to="$a->last_tap" /></td>
                <td><x-status-badge :status="$a->door" /></td>
                <td>
                    @php
                        // Jam keluar lebih awal dari jam masuk = data tap tidak wajar (jam perangkat salah / data ganda)
                        try {
                            $odd = filled($a->first_tap) && filled($a->last_tap)
                                && \Illuminate\Support\Carbon::parse($a->last_tap)->lt(\Illuminate\Support\Carbon::parse($a->first_tap));
                        } catch (\Throwable) { $odd = true; }
                    @endphp
                    @if ($odd)
                        <x-status-badge status="warning" title="Jam keluar lebih awal dari jam masuk, periksa jam perangkat ESP32">Data tidak wajar</x-status-badge>
                    @elseif (filled($a->last_tap))
                        <x-status-badge status="success">Lengkap</x-status-badge>
                    @else
                        <x-status-badge status="warning">Belum tap keluar</x-status-badge>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-table>
@endsection
