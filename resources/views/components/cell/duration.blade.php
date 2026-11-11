{{-- Durasi antara dua waktu, aman untuk null/string/Carbon: <x-cell.duration :from="$a->first_tap" :to="$a->last_tap" /> → "8 j 07 m" --}}
@props(['from' => null, 'to' => null, 'empty' => '-'])
@php
    $parse = function ($v) {
        if ($v instanceof \DateTimeInterface) return \Illuminate\Support\Carbon::instance($v);
        if (is_string($v) && trim($v) !== '') { try { return \Illuminate\Support\Carbon::parse($v); } catch (\Throwable) {} }
        return null;
    };
    $a = $parse($from);
    $b = $parse($to);
    $minutes = ($a && $b && $b->greaterThanOrEqualTo($a)) ? (int) $a->diffInMinutes($b) : null;
@endphp
<span {{ $attributes->class('num whitespace-nowrap') }}>
    @if ($minutes !== null)
        {{ intdiv($minutes, 60) }} j {{ str_pad($minutes % 60, 2, '0', STR_PAD_LEFT) }} m
    @else
        <span class="text-ink-500">{{ $empty }}</span>
    @endif
</span>
