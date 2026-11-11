{{--
    $categories : {id, name}
    $report     : ['stok' => koleksi, 'mutasi' => koleksi, 'absensi' => koleksi, 'akses' => koleksi]
                  (pratinjau, cukup 10–20 baris pertama per bagian; file ekspor berisi data lengkap)
    Ekspor      : GET route('laporan.export') — parameter filter + format=pdf|xlsx, sections[]=...,
                  dan khusus PDF: orientation, paper, signature (lihat docs/kontrak-payload.md)

    Tata letak v1.3:
    ┌ Filter periode & data ────────────────────────┬ Ekspor (sticky) ──────┐
    ├ Pratinjau (tab + tabel scroll) ───────────────┤ format → isi → opsi   │
    │                                               │ [ Unduh ] terlihat    │
    └───────────────────────────────────────────────┴───────────────────────┘
--}}
@extends('layouts.app')
@section('title', 'Laporan')
@section('section', 'Laporan')

@section('content')
    @php
        // Pengaman: bila controller lupa mengirim variabel, halaman tetap tampil dengan data kosong (bukan error 500)
        $__missing = array_values(array_diff(['categories', 'report'], array_keys(get_defined_vars())));
        $categories ??= collect();
        $report ??= [];
        foreach (['stok', 'mutasi', 'absensi', 'akses'] as $__k) { $report[$__k] ??= collect(); }
    @endphp
    <x-dev-missing :vars="$__missing" />
    @php
        // Tanggal dari URL bisa diketik sembarangan (?from=abc): selalu divalidasi sebelum dipakai
        $safeDate = function ($v, $fallback) { try { return $v ? \Illuminate\Support\Carbon::parse($v)->toDateString() : $fallback; } catch (\Throwable) { return $fallback; } };
        $from = $safeDate(request('from'), now()->startOfMonth()->toDateString());
        $to = $safeDate(request('to'), now()->toDateString());
        $sections = [
            'stok' => 'Stok barang',
            'mutasi' => 'Mutasi barang',
            'absensi' => 'Absensi',
            'akses' => 'Log akses pintu',
        ];
        $loc = app()->getLocale();
        $period = \Illuminate\Support\Carbon::parse($from)->locale($loc)->translatedFormat('d M Y') . ' sampai ' . \Illuminate\Support\Carbon::parse($to)->locale($loc)->translatedFormat('d M Y');
    @endphp

    <x-page-header title="Laporan" description="Atur periode, periksa pratinjau, lalu unduh sebagai PDF atau Excel." />

    <x-role-gate :allow="['owner', 'admin']" title="Laporan khusus Owner dan Admin"
                 text="Laporan memuat data absensi dan akses pintu semua orang. Minta Admin atau Owner bila Anda membutuhkan salinannya.">



    <div class="grid items-start gap-6 xl:grid-cols-[minmax(0,1fr)_320px] 2xl:grid-cols-[minmax(0,1fr)_360px]" x-data="{ tab: 'stok' }">
        <div class="min-w-0 space-y-6">
        {{-- Zona 1: filter --}}
        <form id="report-filters" method="GET" class="panel p-4"
              x-data="{
                  from: @js($from),
                  to: @js($to),
                  get invalid() { return this.from && this.to && this.from > this.to },
                  preset(days) {
                      const end = new Date(); const start = new Date();
                      if (days === 'month') start.setDate(1); else start.setDate(end.getDate() - (days - 1));
                      const f = d => d.toLocaleDateString('sv-SE'); // YYYY-MM-DD zona lokal
                      this.from = f(start); this.to = f(end);
                      this.$nextTick(() => this.$el.requestSubmit());
                  },
              }"
              x-on:submit="if (invalid) { $event.preventDefault(); return } $dispatch('table-loading', true)">
            @if (request('as'))<input type="hidden" name="as" value="{{ request('as') }}">@endif
            <div class="flex flex-wrap items-end gap-x-4 gap-y-3">
                <fieldset class="min-w-0">
                    <legend class="label">Periode</legend>
                    <div class="flex flex-wrap items-center gap-2">
                        <input type="date" name="from" x-model="from" max="{{ now()->toDateString() }}" aria-label="Dari tanggal"
                               class="input w-auto" x-bind:class="invalid && 'input-error'">
                        <span class="text-ink-500">sampai</span>
                        <input type="date" name="to" x-model="to" max="{{ now()->toDateString() }}" aria-label="Sampai tanggal"
                               class="input w-auto" x-bind:class="invalid && 'input-error'">
                    </div>
                </fieldset>
                <div class="flex gap-1" role="group" aria-label="Pilihan periode cepat">
                    <button type="button" class="btn btn-ghost btn-sm" x-on:click="preset(1)">Hari ini</button>
                    <button type="button" class="btn btn-ghost btn-sm" x-on:click="preset(7)">7 hari</button>
                    <button type="button" class="btn btn-ghost btn-sm" x-on:click="preset('month')">Bulan ini</button>
                </div>
                <x-field label="Kategori barang" for="category" class="min-w-[190px] flex-1 sm:max-w-[260px]">
                    <select id="category" name="category" class="input">
                        <option value="">Semua kategori</option>
                        @foreach ($categories as $c)
                            <option value="{{ $c->id }}" @selected((string) request('category') === (string) $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </x-field>
                <x-field label="Pintu" for="door">
                    <select id="door" name="door" class="input w-auto">
                        <option value="">Gudang & ICT</option>
                        <option value="gudang" @selected(request('door') === 'gudang')>Pintu gudang</option>
                        <option value="ict" @selected(request('door') === 'ict')>Ruang ICT</option>
                    </select>
                </x-field>
                <x-button type="submit" variant="secondary" icon="filter" x-bind:disabled="invalid">Terapkan</x-button>
            </div>
            <p class="error-text" x-show="invalid" x-cloak role="alert">Tanggal awal tidak boleh setelah tanggal akhir.</p>
        </form>

        {{-- Zona 2: pratinjau --}}
        <section class="min-w-0" aria-labelledby="preview-title">
            <div class="mb-3 flex flex-wrap items-baseline justify-between gap-2">
                <h2 id="preview-title" class="panel-title">Pratinjau</h2>
                <p class="num text-sm text-ink-500">{{ $period }}</p>
            </div>
            <div class="flex gap-1 overflow-x-auto border-b border-concrete-300" role="tablist" aria-label="Bagian laporan"
                 x-on:keydown.right.prevent="$focus.wrap().next(); $focus.focused().click()"
                 x-on:keydown.left.prevent="$focus.wrap().prev(); $focus.focused().click()">
                @foreach ($sections as $key => $label)
                    <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}"
                            class="-mb-px h-11 shrink-0 border-b-2 px-3.5 text-[15px] font-semibold whitespace-nowrap transition-colors"
                            x-bind:class="tab === '{{ $key }}' ? 'border-ink-900 text-ink-900' : 'border-transparent text-ink-500 hover:text-ink-900'"
                            x-bind:aria-selected="tab === '{{ $key }}'" x-bind:tabindex="tab === '{{ $key }}' ? 0 : -1"
                            x-on:click="tab = '{{ $key }}'">
                        {{ $label }} <span class="num ml-1 text-[13px] font-medium text-ink-500">{{ count($report[$key]) }}</span>
                    </button>
                @endforeach
            </div>

            <div class="mt-3" id="panel-stok" role="tabpanel" aria-labelledby="tab-stok" x-show="tab === 'stok'">
                <x-table :rows="$report['stok']" caption="Pratinjau stok barang" scroll empty-title="Tidak ada data stok untuk filter ini">
                    <x-slot:head><tr><th>Barang</th><th>Kategori</th><th class="text-right">Masuk</th><th class="text-right">Keluar</th><th class="text-right">Sisa</th></tr></x-slot:head>
                    @foreach ($report['stok'] as $r)
                        <tr>
                            <td><x-cell.text :value="$r->name" max="30ch" strong /></td>
                            <td><x-cell.text :value="$r->category" max="18ch" class="text-ink-500" /></td>
                            <td class="num text-right text-in-700">{{ (int) ($r->total_in ?? 0) }}</td>
                            <td class="num text-right text-out-700">{{ (int) ($r->total_out ?? 0) }}</td>
                            <td class="num text-right font-semibold">{{ (int) $r->stock }}</td>
                        </tr>
                    @endforeach
                </x-table>
            </div>
            <div class="mt-3" id="panel-mutasi" role="tabpanel" aria-labelledby="tab-mutasi" x-show="tab === 'mutasi'" x-cloak>
                <x-table :rows="$report['mutasi']" caption="Pratinjau mutasi barang" scroll empty-title="Tidak ada mutasi barang di periode ini">
                    <x-slot:head><tr><th>Waktu</th><th>Jenis</th><th>Barang</th><th class="text-right">Jumlah</th><th>Oleh</th></tr></x-slot:head>
                    @foreach ($report['mutasi'] as $r)
                        <tr>
                            <td><x-cell.datetime :value="$r->at" /></td>
                            <td><x-status-badge :status="$r->type" /></td>
                            <td><x-cell.text :value="$r->item_name" max="28ch" strong /></td>
                            <td class="num text-right">{{ (int) $r->qty }}</td>
                            <td><x-cell.person :name="$r->user" :role="$r->role ?? null" /></td>
                        </tr>
                    @endforeach
                </x-table>
            </div>
            <div class="mt-3" id="panel-absensi" role="tabpanel" aria-labelledby="tab-absensi" x-show="tab === 'absensi'" x-cloak>
                <x-table :rows="$report['absensi']" caption="Pratinjau absensi" scroll empty-title="Tidak ada absensi di periode ini">
                    <x-slot:head><tr><th>Nama</th><th>Tanggal</th><th>Masuk</th><th>Keluar</th></tr></x-slot:head>
                    @foreach ($report['absensi'] as $r)
                        <tr>
                            <td><x-cell.person :name="$r->name" :role="$r->role" /></td>
                            <td><x-cell.datetime :value="$r->date" mode="date" /></td>
                            <td class="font-semibold text-in-700"><x-cell.datetime :value="$r->first_tap" mode="time" /></td>
                            <td @class(['font-semibold', 'text-out-700' => filled($r->last_tap)])><x-cell.datetime :value="$r->last_tap" mode="time" empty="Belum tap" /></td>
                        </tr>
                    @endforeach
                </x-table>
            </div>
            <div class="mt-3" id="panel-akses" role="tabpanel" aria-labelledby="tab-akses" x-show="tab === 'akses'" x-cloak>
                <x-table :rows="$report['akses']" caption="Pratinjau log akses pintu" scroll empty-title="Tidak ada akses pintu di periode ini">
                    <x-slot:head><tr><th>Waktu</th><th>Nama</th><th>Pintu</th><th>Arah</th><th>Hasil</th></tr></x-slot:head>
                    @foreach ($report['akses'] as $r)
                        <tr>
                            <td><x-cell.datetime :value="$r->at" /></td>
                            <td><x-cell.person :name="$r->name" :role="$r->role" /></td>
                            <td><x-status-badge :status="$r->door" /></td>
                            <td><x-status-badge :status="$r->direction" /></td>
                            <td><x-status-badge :status="$r->result" /></td>
                        </tr>
                    @endforeach
                </x-table>
            </div>
        </section>
        </div>

        {{-- Zona 3: konfigurasi ekspor (sejajar filter: tombol Unduh terlihat tanpa scroll) --}}
        <aside class="panel flex flex-col xl:sticky xl:top-24 xl:max-h-[calc(100dvh-7.5rem)]"
               x-data="reportExport({ endpoint: @js(route('laporan.export')), form: 'report-filters' })" aria-labelledby="export-title">
            <header class="panel-head">
                <div>
                    <h2 id="export-title" class="panel-title">Ekspor dokumen</h2>
                    <p class="num mt-0.5 text-sm text-ink-500">{{ $period }}</p>
                </div>
            </header>

            <div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-5 py-4">
                {{-- Format: segmented control, menentukan opsi yang tampil di bawahnya --}}
                <div>
                    <p class="label" id="format-label">Format file</p>
                    <div class="grid grid-cols-2 gap-1 rounded-md bg-concrete-100 p-1" role="radiogroup" aria-labelledby="format-label">
                        @foreach (['pdf' => ['PDF', 'file'], 'xlsx' => ['Excel', 'sheet']] as $v => [$l, $ic])
                            <label class="flex h-9 cursor-pointer items-center justify-center gap-2 rounded text-sm font-semibold transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-in-600"
                                   x-bind:class="format === '{{ $v }}' ? 'bg-white text-ink-900 shadow-sm' : 'text-ink-500 hover:text-ink-900'">
                                <input type="radio" name="export-format" value="{{ $v }}" x-model="format" class="sr-only" @checked($v === 'pdf')>
                                <x-icon :name="$ic" :size="16" /> {{ $l }}
                            </label>
                        @endforeach
                    </div>
                </div>

                <fieldset>
                    <legend class="label">Isi laporan</legend>
                    <div class="space-y-1.5">
                        @foreach ($sections as $key => $label)
                            <label class="flex items-center gap-2.5">
                                {{-- checked dari server: tidak berkedip kosong sebelum Alpine siap --}}
                                <input type="checkbox" value="{{ $key }}" x-model="sections" class="check" checked>
                                <span class="flex-1">{{ $label }}</span>
                                <span class="num text-[13px] text-ink-500">{{ count($report[$key]) }}</span>
                            </label>
                        @endforeach
                    </div>
                    <p class="error-text" x-show="!sections.length" x-cloak role="alert">Pilih minimal satu bagian.</p>
                </fieldset>

                {{-- v1.0: legend float + grid membuat tombol orientasi menyusut jadi 0 px. Kini memakai judul biasa. --}}
                <div class="space-y-3 border-t border-concrete-200 pt-3.5" x-show="format === 'pdf'" x-transition.opacity>
                    <p class="label mb-0" id="orientation-label">Orientasi halaman</p>
                    <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-labelledby="orientation-label">
                        @foreach (['portrait' => 'Tegak', 'landscape' => 'Mendatar'] as $v => $l)
                            <label class="flex h-9 cursor-pointer items-center justify-center gap-2 rounded-md border text-sm font-semibold transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-in-600"
                                   x-bind:class="orientation === '{{ $v }}' ? 'border-ink-900 bg-ink-900 text-white' : 'border-concrete-300 hover:border-ink-300'">
                                <input type="radio" name="export-orientation" value="{{ $v }}" x-model="orientation" class="sr-only" @checked($v === 'landscape')>
                                <span aria-hidden="true" @class(['inline-block rounded-[2px] border-2 border-current', 'h-4 w-3' => $v === 'portrait', 'h-3 w-4' => $v === 'landscape'])></span>
                                {{ $l }}
                            </label>
                        @endforeach
                    </div>
                    <p class="label mb-0" id="paper-label">Ukuran kertas</p>
                    <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-labelledby="paper-label">
                        @foreach (['a4' => ['A4', '210 × 297 mm'], 'f4' => ['F4 / Folio', '215 × 330 mm']] as $v => [$l, $size])
                            <label class="flex h-9 cursor-pointer items-center justify-center gap-1.5 rounded-md border text-sm font-semibold transition-colors has-[:focus-visible]:ring-2 has-[:focus-visible]:ring-in-600"
                                   title="{{ $size }}" x-bind:class="paper === '{{ $v }}' ? 'border-ink-900 bg-ink-900 text-white' : 'border-concrete-300 hover:border-ink-300'">
                                <input type="radio" name="export-paper" value="{{ $v }}" x-model="paper" class="sr-only" @checked($v === 'a4')>{{ $l }}
                            </label>
                        @endforeach
                    </div>
                    <label class="flex items-start gap-2.5">
                        <input type="checkbox" x-model="signature" class="check mt-1" checked>
                        <span>Sertakan kolom tanda tangan penanggung jawab</span>
                    </label>
                </div>
                <p class="hint mt-0!" x-show="format === 'xlsx'" x-cloak>Excel berisi satu sheet untuk setiap bagian yang dipilih.</p>
            </div>

            {{-- Tombol unduh selalu terlihat di bawah panel, tidak tenggelam di bawah lipatan layar --}}
            <footer class="border-t border-concrete-200 bg-concrete-50 p-4">
                <x-button icon="download" class="h-11 w-full" x-on:click="download(format)" x-bind:disabled="!sections.length" loading="exporting">
                    <span x-text="exporting ? 'Menyiapkan file' : (format === 'pdf' ? 'Unduh PDF' : 'Unduh Excel')"></span>
                </x-button>
                <p class="hint text-center" x-text="sections.length + ' dari 4 bagian akan diekspor'"></p>
            </footer>
        </aside>
    </div>

    </x-role-gate>
@endsection
