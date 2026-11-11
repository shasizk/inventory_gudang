{{--
    $stocks  : {id, name, category, total_in, total_out, stock, min_stock, unit}
    $summary : ['all' => n, 'aman' => n, 'menipis' => n, 'habis' => n]
    Filter status dikirim sebagai ?status=aman|menipis|habis
--}}
@extends('layouts.app')
@section('title', 'Stok barang')
@section('section', 'Barang keluar')

@section('content')
    @php
        // Pengaman: bila controller lupa mengirim variabel, halaman tetap tampil dengan data kosong (bukan error 500)
        $__missing = array_values(array_diff(['stocks', 'summary'], array_keys(get_defined_vars())));
        $stocks ??= collect();
        $summary ??= [];
    @endphp
    <x-dev-missing :vars="$__missing" />
    <x-page-header title="Stok barang" description="Sisa stok setiap barang: total masuk dikurangi total keluar." />

    {{-- Filter cepat berdasarkan kondisi stok --}}
    @php
        $chips = [
            '' => ['Semua barang', (int) ($summary['all'] ?? 0), 'neutral'],
            'aman' => ['Aman', (int) ($summary['aman'] ?? 0), 'ok'],
            'menipis' => ['Menipis', (int) ($summary['menipis'] ?? 0), 'warn'],
            'habis' => ['Habis', (int) ($summary['habis'] ?? 0), 'bad'],
        ];
        $current = request('status', '');
    @endphp
    <nav class="mb-4 flex flex-wrap gap-2" aria-label="Filter kondisi stok">
        @foreach ($chips as $key => [$label, $count, $tone])
            <a href="{{ request()->fullUrlWithQuery(['status' => $key ?: null, 'page' => null]) }}"
               @class([
                   'inline-flex h-9 items-center gap-2 rounded-md border px-3 text-sm font-semibold transition-colors',
                   'border-ink-900 bg-ink-900 text-white' => $current === $key,
                   'border-concrete-300 bg-white text-ink-700 hover:border-ink-300' => $current !== $key,
               ])
               @if ($current === $key) aria-current="true" @endif>
                {{ $label }}
                <span @class(['num rounded px-1.5 text-[13px]', 'bg-white/15' => $current === $key, "badge-$tone" => $current !== $key])>{{ $count }}</span>
            </a>
        @endforeach
    </nav>

    <x-table :rows="$stocks" empty-icon="boxes"
                  empty-title="Belum ada stok tercatat" empty-text="Stok muncul setelah Owner mencatat barang masuk pertama.">
        <x-slot:toolbar>
            <x-filter-bar placeholder="Cari nama barang atau kategori">
                @if (request('status'))<input type="hidden" name="status" value="{{ request('status') }}">@endif
            </x-filter-bar>
        </x-slot:toolbar>
        <x-slot:head>
            <tr>
                <th>Barang</th>
                <th>Kategori</th>
                <th class="text-right">Total masuk</th>
                <th class="text-right">Total keluar</th>
                <th class="min-w-[180px]">Sisa stok</th>
                <th>Kondisi</th>
            </tr>
        </x-slot:head>

        @foreach ($stocks as $s)
            @php
                // Selalu dijadikan angka: PDO/MySQL bisa mengirim "0" (teks) yang membuat === 0 bernilai salah
                $qty = (int) $s->stock;
                $min = (int) ($s->min_stock ?? 0);
                $in = (int) ($s->total_in ?? 0);
                $state = $qty <= 0 ? 'habis' : ($qty <= $min ? 'menipis' : 'aman');
                $ratio = $in > 0 ? min(100, max(0, $qty / $in * 100)) : 0;
            @endphp
            <tr>
                <td><x-cell.text :value="$s->name" max="32ch" strong /></td>
                <td><x-cell.text :value="$s->category" max="20ch" class="text-ink-500" /></td>
                <td class="num text-right text-in-700">{{ $in }}</td>
                <td class="num text-right text-out-700">{{ (int) ($s->total_out ?? 0) }}</td>
                <td>
                    <div class="flex items-center gap-3">
                        <span class="num w-16 shrink-0 font-display text-xl font-semibold whitespace-nowrap">{{ $qty }}<span class="ml-1 font-sans text-[13px] font-normal text-ink-500">{{ $s->unit ?: 'unit' }}</span></span>
                        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-concrete-200" title="Minimum {{ $min }} {{ $s->unit ?: 'unit' }}">
                            <div @class(['h-full rounded-full', 'bg-ok-600' => $state === 'aman', 'bg-hazard-500' => $state === 'menipis', 'bg-bad-600' => $state === 'habis']) style="width: {{ $ratio }}%"></div>
                        </div>
                    </div>
                </td>
                <td>
                    {{-- Label pendek agar tidak terpotong di tepi tabel; batas minimum di baris kedua --}}
                    <x-status-badge :status="$state" />
                    @if ($state !== 'aman')<span class="num mt-0.5 block text-[13px] whitespace-nowrap text-ink-500">Min. {{ $min }} {{ $s->unit ?: 'unit' }}</span>@endif
                </td>
            </tr>
        @endforeach

        <x-slot:footer>Batas minimum stok diatur per barang. Barang berstatus Menipis juga tampil di dashboard.</x-slot:footer>
    </x-table>
@endsection
