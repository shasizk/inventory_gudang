{{--
    Tanggal/jam yang TIDAK PERNAH crash, apa pun bentuk datanya:
    Carbon, DateTime, string "2026-10-21 09:12:33", timestamp angka, null, atau teks tidak valid.

    <x-cell.datetime :value="$row->at" />                     21/10/2026 + 09:12 (bertumpuk)
    <x-cell.datetime :value="$row->at" seconds />             jam dengan detik
    <x-cell.datetime :value="$row->date" mode="date" />       21/10/2026
    <x-cell.datetime :value="$row->date" mode="long" />       Rab, 21 Okt 2026
    <x-cell.datetime :value="$row->first_tap" mode="time" empty="Belum tap" />
--}}
@props(['value' => null, 'mode' => 'stacked', 'seconds' => false, 'dateOnly' => false, 'empty' => '-'])
@php
    $mode = $dateOnly ? 'date' : $mode;
    $dt = null;
    $raw = $value;
    if ($value instanceof \DateTimeInterface) {
        $dt = \Illuminate\Support\Carbon::instance($value);
    } elseif (is_numeric($value)) {
        $dt = \Illuminate\Support\Carbon::createFromTimestamp((int) $value, config('app.timezone'));
    } elseif (is_string($value) && trim($value) !== '') {
        try { $dt = \Illuminate\Support\Carbon::parse($value); } catch (\Throwable) { $dt = null; }
    }
    $time = $seconds ? 'H:i:s' : 'H:i';
@endphp
@if ($dt)
    <time datetime="{{ $dt->toIso8601String() }}" {{ $attributes->class('num block whitespace-nowrap') }}>
        @switch($mode)
            @case('date') {{ $dt->format('d/m/Y') }} @break
            @case('time') {{ $dt->format($time) }} @break
            @case('long') {{ $dt->locale(app()->getLocale())->translatedFormat('D, d M Y') }} @break
            @case('inline') {{ $dt->format('d/m/Y ' . $time) }} @break
            @default
                {{ $dt->format('d/m/Y') }}
                <span class="block text-[13px] text-ink-500">{{ $dt->format($time) }}</span>
        @endswitch
    </time>
@else
    {{-- Data kosong atau tidak bisa dibaca: tampilkan apa adanya, jangan crash --}}
    <span {{ $attributes->class('block whitespace-nowrap text-ink-500') }} @if (filled($raw)) title="Format tanggal tidak dikenali: {{ is_scalar($raw) ? $raw : gettype($raw) }}" @endif>
        {{ filled($raw) && is_scalar($raw) ? $raw : $empty }}
    </span>
@endif
