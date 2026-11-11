{{--
    <x-button variant="primary|secondary|ghost|danger|scan" size="md|sm" icon="plus" href="..." loading="saving">
    - loading : ekspresi Alpine (opsional). Saat bernilai true, tombol menampilkan spinner & nonaktif.
    - Gunakan x-on:click / x-bind: (bukan @click / :attr) di tag komponen Blade.
--}}
@props(['variant' => 'primary', 'size' => 'md', 'icon' => null, 'href' => null, 'loading' => null])
@php
    $classes = 'btn btn-' . $variant . ($size === 'sm' ? ' btn-sm' : '');
    $iconSize = $size === 'sm' ? 16 : 18;
    // Gabungkan x-bind:disabled bawaan halaman dengan status loading agar atribut tidak dobel
    $extraDisabled = $attributes->get('x-bind:disabled');
    $disabledExpr = collect([$loading, $extraDisabled])->filter()->map(fn ($e) => "($e)")->join(' || ');
    $attributes = $attributes->except('x-bind:disabled');
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon)<x-icon :name="$icon" :size="$iconSize" />@endif
        {{ $slot }}
    </a>
@else
    <button {{ $attributes->merge(['type' => 'button', 'class' => $classes]) }}
        @if ($disabledExpr) x-bind:disabled="{{ $disabledExpr }}" @endif
        @if ($loading) x-bind:aria-busy="{{ $loading }}" @endif>
        @if ($loading)
            <span class="spinner" x-show="{{ $loading }}" x-cloak></span>
            @if ($icon)<span x-show="!({{ $loading }})"><x-icon :name="$icon" :size="$iconSize" /></span>@endif
        @elseif ($icon)
            <x-icon :name="$icon" :size="$iconSize" />
        @endif
        {{ $slot }}
    </button>
@endif
