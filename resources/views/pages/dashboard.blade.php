{{--
    Variabel dari controller:
    $stats    : ['stock_total','sku','in_today','out_today','low_stock','people_inside']
    $lowStock : koleksi {name, category, stock, min_stock}
--}}
@extends('layouts.app')
@section('title', 'Dashboard')
@section('section', 'Dashboard')

@section('content')
    @php
        // Pengaman: bila controller lupa mengirim variabel, halaman tetap tampil dengan data kosong (bukan error 500)
        $__missing = array_values(array_diff(['stats', 'lowStock'], array_keys(get_defined_vars())));
        $stats ??= [];
        $lowStock ??= collect();
    @endphp
    <x-dev-missing :vars="$__missing" />
    @php
        $user = auth()->user();
        $isDemo = config('app.ui_demo', app()->isLocal());
        $fullName = $user->name ?? $user->username ?? ($isDemo ? 'Dewi Lestari' : 'Pengguna');
        $firstName = \Illuminate\Support\Str::of($fullName)->squish()->before(' ')->toString() ?: $fullName;
        $lowItems = collect($lowStock instanceof \Illuminate\Contracts\Pagination\Paginator ? $lowStock->items() : $lowStock);
        $outCount = $lowItems->filter(fn ($i) => (int) $i->stock <= 0)->count();
        $lowCount = $lowItems->count() - $outCount;
        // Pintasan sesuai peran: hanya tindakan yang memang boleh dilakukan peran tersebut
        $actions = match ($currentRole ?? 'guest') {
            'owner' => [['Scan barang masuk', 'scan', route('barang-masuk.index') . '#scan', 'scan'], ['Daftarkan kartu', 'id-card', route('cards.index', ['baru' => 1]), 'secondary'], ['Unduh laporan', 'download', route('laporan.index'), 'secondary']],
            'admin' => [['Scan barang keluar', 'scan', route('barang-keluar.index') . '#scan', 'scan'], ['Daftarkan kartu', 'id-card', route('cards.index', ['baru' => 1]), 'secondary'], ['Unduh laporan', 'download', route('laporan.index'), 'secondary']],
            'teknisi', 'noc' => [['Scan barang keluar', 'scan', route('barang-keluar.index') . '#scan', 'scan'], ['Riwayat pintu', 'door', route('history.door'), 'secondary']],
            default => [],
        };
    @endphp

    {{-- Pembuka: siapa, kapan, apa yang perlu diperhatikan, dan tindakan tercepat --}}
    <section class="mb-6 flex flex-wrap items-end justify-between gap-x-8 gap-y-5" aria-labelledby="greeting"
             x-data="{
                 now: new Date(),
                 init() { setInterval(() => (this.now = new Date()), 30000) },
                 get greeting() { const h = this.now.getHours(); return h < 11 ? 'Selamat pagi' : h < 15 ? 'Selamat siang' : h < 18 ? 'Selamat sore' : 'Selamat malam' },
                 get today() { return this.now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) + ', pukul ' + this.now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) },
                 get alerts() {
                     const list = [];
                     this.$store.door.doors.forEach(d => {
                         if (!d.online) list.push({ tone: 'bad', text: d.short + ' offline', href: '#door-title' });
                         else if (d.pending) list.push({ tone: 'pending', text: d.short + ' menunggu verifikasi', href: '#door-title' });
                     });
                     if ({{ $outCount }}) list.push({ tone: 'bad', text: '{{ $outCount }} barang habis', href: @js(route('stok.index', ['status' => 'habis'])) });
                     if ({{ $lowCount }}) list.push({ tone: 'warn', text: '{{ $lowCount }} barang menipis', href: @js(route('stok.index', ['status' => 'menipis'])) });
                     return list;
                 },
             }">
        <div class="min-w-0">
            <p class="text-sm font-medium text-ink-500" x-text="today">{{ now()->locale(app()->getLocale())->translatedFormat('l, d F Y') }}</p>
            <h1 id="greeting" class="mt-1 font-display text-[28px] leading-[1.15] font-semibold tracking-[-0.01em] sm:text-[32px]">
                <span x-text="greeting">Halo</span>, {{ $firstName }}
            </h1>
            {{-- Ringkasan hal yang perlu diperhatikan; setiap butir bisa diklik menuju detailnya --}}
            <div class="mt-3 flex flex-wrap items-center gap-2" aria-live="polite">
                <template x-for="a in alerts" :key="a.text">
                    <a x-bind:href="a.href" class="badge h-7 px-2.5 text-sm hover:underline"
                       x-bind:class="{ 'badge-bad': a.tone === 'bad', 'badge-warn': a.tone === 'warn', 'badge-pending': a.tone === 'pending' }">
                        <span class="size-1.5 rounded-full bg-current" aria-hidden="true"></span><span x-text="a.text"></span>
                    </a>
                </template>
                <span x-show="!alerts.length && $store.door.doors.length" x-cloak class="badge badge-ok h-7 px-2.5 text-sm">
                    <x-icon name="check" :size="14" /> Semua pintu terhubung dan stok aman
                </span>
            </div>
        </div>
        @if ($actions)
            <nav class="flex w-full flex-wrap gap-2 sm:w-auto" aria-label="Pintasan">
                @foreach ($actions as [$label, $icon, $href, $variant])
                    <a href="{{ $href }}" @class(['btn flex-1 sm:flex-none', 'btn-scan' => $variant === 'scan', 'btn-secondary' => $variant !== 'scan'])>
                        <x-icon :name="$icon" :size="18" /> {{ $label }}
                    </a>
                @endforeach
            </nav>
        @endif
    </section>

    <div class="space-y-6">
        <x-door-status />

        {{-- Ringkasan angka: satu strip, bukan deretan kartu --}}
        <section class="panel grid grid-cols-2 lg:grid-cols-5" aria-label="Ringkasan stok dan sirkulasi">
            @php
                $n = fn ($k) => (int) ($stats[$k] ?? 0); // angka dari DB bisa berupa teks "54"
                $figures = [
                    ['Total stok di gudang', number_format($n('stock_total'), 0, ',', '.'), $n('sku') . ' jenis barang', 'text-ink-900'],
                    ['Barang masuk hari ini', number_format($n('in_today'), 0, ',', '.'), 'unit tercatat Owner', 'text-in-600'],
                    ['Barang keluar hari ini', number_format($n('out_today'), 0, ',', '.'), 'unit dari hasil scan', 'text-out-600'],
                    ['Perlu diisi ulang', $n('low_stock'), 'habis atau di bawah minimum', $n('low_stock') ? 'text-warn-600' : 'text-ok-600'],
                    ['Orang di dalam ruangan', $n('people_inside'), 'gudang dan ruang ICT', 'text-ink-900'],
                ];
            @endphp
            @foreach ($figures as $i => [$label, $value, $note, $color])
                <div @class([
                    'border-concrete-200 px-5 py-4',
                    'border-b lg:border-b-0 lg:border-r' => $i < 4,
                    'border-r' => $i % 2 === 0 && $i < 4,
                    'col-span-2 lg:col-span-1' => $i === 4,
                ])>
                    <p class="text-sm font-medium text-ink-500">{{ $label }}</p>
                    <p class="num mt-1 font-display text-[34px] leading-none font-semibold {{ $color }}">{{ $value }}</p>
                    <p class="mt-1.5 text-[13px] text-ink-500">{{ $note }}</p>
                </div>
            @endforeach
        </section>

        <div class="grid gap-6 xl:grid-cols-[minmax(0,1.65fr)_minmax(0,1fr)]">
            {{-- Grafik sirkulasi --}}
            <section class="panel" x-data="circulationChart({ endpoint: @js(route('ajax.stats.circulation')) })" aria-labelledby="chart-title">
                <header class="panel-head">
                    <div>
                        <h2 id="chart-title" class="panel-title">Sirkulasi barang</h2>
                        <p class="mt-0.5 flex flex-wrap gap-x-4 text-sm text-ink-500">
                            <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-in-600"></span>Masuk <b class="num text-ink-900" x-text="totals.in"></b></span>
                            <span class="inline-flex items-center gap-1.5"><span class="size-2.5 rounded-sm bg-out-600"></span>Keluar <b class="num text-ink-900" x-text="totals.out"></b></span>
                        </p>
                    </div>
                    <div class="inline-flex rounded-md border border-concrete-300 bg-concrete-50 p-0.5" role="group" aria-label="Rentang waktu">
                        @foreach (['7d' => '7 hari', '30d' => '30 hari'] as $key => $label)
                            <button type="button" class="h-8 rounded px-3 text-sm font-semibold transition-colors"
                                    x-bind:class="range === '{{ $key }}' ? 'bg-white text-ink-900 shadow-sm' : 'text-ink-500 hover:text-ink-900'"
                                    x-bind:aria-pressed="range === '{{ $key }}'" x-on:click="range = '{{ $key }}'">{{ $label }}</button>
                        @endforeach
                    </div>
                </header>
                <div class="relative h-[300px] p-4">
                    <canvas x-ref="canvas" role="img" aria-label="Grafik batang jumlah barang masuk dan keluar per hari"></canvas>
                    <div x-show="loading" class="absolute inset-4 flex items-end gap-2 bg-white" aria-hidden="true">
                        @foreach ([40, 65, 30, 80, 55, 70, 45] as $h)
                            <div class="skeleton flex-1" style="height: {{ $h }}%"></div>
                        @endforeach
                    </div>
                    <div x-show="!loading && (error || empty)" x-cloak class="absolute inset-4 grid place-items-center bg-white text-center">
                        <div>
                            <p class="font-semibold" x-text="error ? 'Grafik tidak dapat dimuat' : 'Belum ada pergerakan barang di rentang ini'"></p>
                            <p class="mt-1 text-sm text-ink-500" x-text="error ? error : 'Scan barang masuk atau keluar akan langsung terlihat di sini.'"></p>
                            <button type="button" class="btn btn-secondary btn-sm mt-3" x-show="error" x-on:click="load()">Muat ulang</button>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Live log --}}
            <section class="panel flex flex-col" x-data="liveFeed({ endpoint: @js(route('ajax.activity.latest')) })" aria-labelledby="feed-title">
                <header class="panel-head">
                    <div>
                        <h2 id="feed-title" class="panel-title">Aktivitas terbaru</h2>
                        <p class="mt-0.5 flex items-center gap-1.5 text-sm text-ink-500">
                            <span class="size-2 rounded-full" x-bind:class="paused ? 'bg-ink-300' : 'bg-ok-600 animate-pulse'"></span>
                            <span x-text="paused ? 'Pembaruan dijeda' : 'Langsung, tiap 5 detik'"></span>
                        </p>
                    </div>
                    <x-button variant="ghost" size="sm" x-on:click="paused = !paused" x-text="paused ? 'Lanjutkan' : 'Jeda'" />
                </header>

                <ul class="flex-1 divide-y divide-concrete-200" x-show="items.length" aria-live="polite">
                    <template x-for="item in items" :key="item.id">
                        <li class="flex gap-3 px-5 py-3" x-bind:class="item.fresh && 'row-fresh'">
                            <span class="badge mt-0.5 h-fit" x-bind:class="meta(item).badge" x-text="meta(item).label"></span>
                            <div class="min-w-0 flex-1">
                                <p class="text-[15px] leading-snug">
                                    <span class="font-semibold" x-text="item.actor"></span>
                                    <span class="text-ink-500" x-show="item.role" x-text="'(' + item.role + ')'"></span>
                                </p>
                                <p class="text-sm leading-snug text-ink-700" x-text="item.detail"></p>
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="badge" x-bind:class="item.location === 'ict' ? 'badge-outline' : 'badge-dark'" x-text="item.location === 'ict' ? 'ICT' : 'Gudang'"></span>
                                <p class="mt-1 text-[13px] text-ink-500" x-text="when(item)"></p>
                            </div>
                        </li>
                    </template>
                </ul>
                <div x-show="loading" class="space-y-4 px-5 py-4" aria-hidden="true">
                    @for ($i = 0; $i < 5; $i++)
                        <div class="flex gap-3"><div class="skeleton h-5 w-10"></div><div class="flex-1 space-y-1.5"><div class="skeleton h-4 w-2/3"></div><div class="skeleton h-3.5 w-1/2"></div></div></div>
                    @endfor
                </div>
                <div x-show="!loading && !items.length" x-cloak>
                    <x-empty-state icon="history" title="Belum ada aktivitas hari ini" text="Tap kartu di pintu atau scan barang, aktivitasnya muncul di sini." />
                </div>
                <footer class="border-t border-concrete-200 px-5 py-3">
                    @if ($canManage)
                        <a href="{{ route('history.audit') }}" class="text-sm font-semibold text-in-700 hover:underline">Lihat semua di audit log</a>
                    @else
                        <a href="{{ route('history.door') }}" class="text-sm font-semibold text-in-700 hover:underline">Lihat riwayat pintu</a>
                    @endif
                </footer>
            </section>
        </div>

        {{-- Stok yang perlu diisi ulang --}}
        <x-panel title="Perlu diisi ulang" description="Barang dengan stok di bawah batas minimum">
            <x-slot:actions>
                <x-button variant="ghost" size="sm" :href="route('stok.index', ['status' => 'menipis'])">Lihat semua stok</x-button>
            </x-slot:actions>
            @if ($lowStock->isEmpty())
                <x-empty-state icon="check" title="Semua stok aman" text="Tidak ada barang di bawah batas minimum." />
            @else
                <ul class="divide-y divide-concrete-200">
                    @foreach ($lowStock as $item)
                        @php $qty = (int) $item->stock; $min = (int) $item->min_stock; @endphp
                        <li class="flex flex-wrap items-center gap-x-6 gap-y-2 px-5 py-3">
                            <div class="min-w-0 flex-1">
                                <x-cell.text :value="$item->name" max="40ch" strong />
                                <p class="text-sm text-ink-500">{{ $item->category ?: '-' }}</p>
                            </div>
                            <div class="w-40">
                                <div class="h-1.5 overflow-hidden rounded-full bg-concrete-200">
                                    <div class="h-full rounded-full {{ $qty <= 0 ? 'bg-bad-600' : 'bg-hazard-500' }}" style="width: {{ $min > 0 ? min(100, max(0, $qty / $min * 100)) : 0 }}%"></div>
                                </div>
                                <p class="num mt-1 text-[13px] text-ink-500">{{ $qty }} dari minimum {{ $min }}</p>
                            </div>
                            <x-status-badge :status="$qty <= 0 ? 'habis' : 'menipis'" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </x-panel>
    </div>
@endsection
