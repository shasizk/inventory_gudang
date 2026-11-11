{{--
    Teks panjang dipotong rapi, isi lengkap tetap bisa dibaca lewat tooltip & screen reader.
    <x-cell.text :value="$row->item_name" max="28ch" strong />
    lines="2" memotong di baris kedua. Nilai kosong/null tampil sebagai "-".
--}}
@props(['value' => null, 'max' => '32ch', 'lines' => 1, 'strong' => false, 'sub' => null, 'empty' => '-'])
@php
    $text = is_scalar($value) ? (string) $value : '';
    $lines = (int) $lines;
@endphp
<div {{ $attributes->class('min-w-0') }} style="max-width: {{ $max }}">
    @if ($text !== '')
        <span @class(['block', 'truncate' => $lines <= 1, 'line-clamp-2' => $lines === 2, 'line-clamp-3' => $lines >= 3, 'font-semibold' => $strong]) title="{{ $text }}">{{ $text }}</span>
    @else
        <span class="block text-ink-500">{{ $empty }}</span>
    @endif
    @if (filled($sub) && is_scalar($sub))<span class="block truncate text-[13px] text-ink-500" title="{{ $sub }}">{{ $sub }}</span>@endif
</div>
