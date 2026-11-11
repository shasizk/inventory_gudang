{{--
    <x-table :rows="$logs" caption="Riwayat akses pintu" scroll empty-title="..." empty-text="..." empty-icon="door">
        <x-slot:toolbar> <x-filter-bar /> </x-slot:toolbar>
        <x-slot:head> <tr><th>...</th></tr> </x-slot:head>
        @foreach ($logs as $log) <tr>...</tr> @endforeach
        <x-slot:empty-action> tombol </x-slot:empty-action>
    </x-table>

    rows       : Collection | Paginator | LengthAwarePaginator | CursorPaginator (dipakai untuk empty state & pagination)
    caption    : judul tabel untuk screen reader (tidak terlihat)
    scroll     : tinggi maksimum + header lengket, untuk log panjang (riwayat, audit)
    stack      : (bawaan true) di layar < 640 px setiap baris tampil sebagai kartu berlabel, tanpa geser ke samping
    loading    : $dispatch('table-loading', true) — otomatis dari <x-filter-bar> & link pagination
--}}
@props([
    'rows' => null,
    'empty' => null,
    'caption' => null,
    'scroll' => false,
    'emptyTitle' => 'Belum ada data',
    'emptyText' => null,
    'emptyIcon' => 'boxes',
    'skeletonRows' => 6,
    'stack' => true,
])
@php
    $isEmpty = $empty ?? ($rows === null ? false : (is_countable($rows) ? count($rows) === 0 : $rows->isEmpty()));
    $filtered = collect(request()->except(['page', 'cursor', 'as']))->filter(fn ($v) => filled($v))->isNotEmpty();
    $paginates = $rows instanceof \Illuminate\Contracts\Pagination\Paginator
        || $rows instanceof \Illuminate\Contracts\Pagination\CursorPaginator;
    $total = $rows instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator ? $rows->total() : null;
@endphp
<div {{ $attributes->class('panel overflow-hidden') }} x-data="{ loading: false }" x-on:table-loading.window="loading = $event.detail"
     @if ($stack)
         {{-- Label kolom disalin ke tiap sel (data-label) untuk tampilan kartu di HP --}}
         x-init="const heads = [...$el.querySelectorAll('thead th')].map((th) => (th.querySelector('.sr-only') ? '' : th.textContent.trim()));
                 $el.querySelectorAll('tbody tr:not(.is-empty)').forEach((tr) => [...tr.children].forEach((td, i) => { if (!td.hasAttribute('data-label')) td.dataset.label = heads[i] ?? '' }))"
     @endif>
    @isset($toolbar)
        <div class="border-b border-concrete-200 px-4 py-3">{{ $toolbar }}</div>
    @endisset

    <div class="relative">
        <div @class(['overflow-auto', 'table-scroll' => $scroll]) x-bind:class="loading && 'opacity-40'" x-bind:aria-busy="loading" tabindex="{{ $scroll ? 0 : -1 }}"
             @if ($scroll) role="region" aria-label="{{ $caption ?? 'Tabel data' }}" @endif>
            <table @class(['tbl', 'tbl-stack' => $stack])>
                @if ($caption)<caption class="sr-only">{{ $caption }}</caption>@endif
                <thead>{{ $head }}</thead>
                <tbody>
                    @if ($isEmpty)
                        <tr class="is-empty">
                            <td colspan="99" class="p-0!">
                                @if ($filtered)
                                    <x-empty-state icon="search" title="Tidak ada data yang cocok dengan filter" text="Coba ubah kata kunci atau perluas rentang tanggal. Data di luar filter tetap aman.">
                                        <a href="{{ url()->current() }}{{ request('as') ? '?as=' . request('as') : '' }}" class="btn btn-secondary">Reset filter</a>
                                    </x-empty-state>
                                @else
                                    <x-empty-state :icon="$emptyIcon" :title="$emptyTitle" :text="$emptyText">
                                        {{ $emptyAction ?? '' }}
                                    </x-empty-state>
                                @endif
                            </td>
                        </tr>
                    @else
                        {{ $slot }}
                    @endif
                </tbody>
            </table>
        </div>

        <div x-show="loading" x-cloak class="absolute inset-0 space-y-3 bg-white/70 px-4 pt-12" aria-hidden="true">
            @for ($i = 0; $i < $skeletonRows; $i++)
                <div class="flex gap-4"><div class="skeleton h-4 w-24"></div><div class="skeleton h-4 flex-1"></div><div class="skeleton h-4 w-16"></div></div>
            @endfor
        </div>
    </div>

    @if (($paginates && $rows->hasPages()) || $total !== null || isset($footer))
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-concrete-200 px-4 py-3 text-sm text-ink-500"
             x-on:click="$event.target.closest('a[href]') && !$event.ctrlKey && !$event.metaKey && !$event.shiftKey && $event.button === 0 && (loading = true)">
            <div>
                @isset($footer){{ $footer }}@endisset
                @if ($total !== null && ! isset($footer))
                    Menampilkan <span class="num font-semibold text-ink-800">{{ $rows->firstItem() ?? 0 }}–{{ $rows->lastItem() ?? 0 }}</span>
                    dari <span class="num font-semibold text-ink-800">{{ number_format($total, 0, ',', '.') }}</span> data
                @endif
            </div>
            @if ($paginates && $rows->hasPages())
                <div class="min-w-0">{{ $rows->withQueryString()->links() }}</div>
            @endif
        </div>
    @endif
</div>
