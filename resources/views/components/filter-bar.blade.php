{{--
    Form filter GET standar. Parameter: q (kata kunci) + filter tambahan di slot.
    <x-filter-bar placeholder="Cari nama barang">
        <select name="category" class="input w-auto">...</select>
    </x-filter-bar>
--}}
@props(['placeholder' => 'Cari data', 'action' => null, 'search' => true])
@php
    $active = collect(request()->except(['page', 'as']))->filter(fn ($v) => filled($v))->isNotEmpty();
    // Jumlah filter tambahan yang sedang aktif (selain kata kunci), ditampilkan di tombol Filter versi HP
    $extraCount = collect(request()->except(['page', 'as', 'q', 'cursor']))->filter(fn ($v) => filled($v))->count();
    $hasExtra = $slot->isNotEmpty();
@endphp
<form method="GET" action="{{ $action ?? url()->current() }}" x-data="{ open: false }" x-on:submit="$dispatch('table-loading', true)"
      {{ $attributes->class('flex flex-wrap items-end gap-2') }}>
    @if (request('as'))<input type="hidden" name="as" value="{{ request('as') }}">@endif
    @if ($search)
        <label class="relative min-w-[220px] flex-1 sm:max-w-sm">
            <span class="sr-only">Cari</span>
            <x-icon name="search" :size="18" class="pointer-events-none absolute top-1/2 left-3 -translate-y-1/2 text-ink-400" />
            <input type="search" name="q" value="{{ request('q') }}" placeholder="{{ $placeholder }}" class="input pl-9">
        </label>
    @endif
    @if ($hasExtra)
        {{-- HP: filter tambahan dilipat agar tabel langsung terlihat --}}
        <button type="button" class="btn btn-secondary sm:hidden" x-on:click="open = !open" x-bind:aria-expanded="open" aria-controls="{{ $fid = 'filters-' . \Illuminate\Support\Str::random(6) }}">
            <x-icon name="filter" :size="18" /> Filter
            @if ($extraCount)<span class="num rounded bg-ink-900 px-1.5 text-[13px] text-white">{{ $extraCount }}</span>@endif
        </button>
        <div id="{{ $fid }}" class="filter-extra flex w-full flex-wrap items-end gap-2 sm:w-auto" x-bind:class="open && 'is-open'">
            {{ $slot }}
        </div>
    @endif
    <x-button type="submit" variant="secondary" icon="search" class="max-sm:flex-1">Terapkan</x-button>
    @if ($active)
        <a href="{{ url()->current() }}" class="btn btn-ghost">Reset</a>
    @endif
    @isset($actions)
        {{ $actions }}
    @endisset
</form>
