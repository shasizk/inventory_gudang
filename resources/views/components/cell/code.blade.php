{{-- Serial number / UID: tidak pernah patah di tengah kode. <x-cell.code :value="$row->serial" /> | group: "A3 F2 19 7C" --}}
@props(['value' => null, 'group' => false, 'empty' => '-'])
@php
    $code = is_scalar($value) ? trim((string) $value) : '';
    $display = $group && $code !== '' ? trim(chunk_split(strtoupper($code), 2, ' ')) : $code;
@endphp
@if ($code !== '')
    <span {{ $attributes->class('code whitespace-nowrap') }} title="{{ $code }}">{{ $display }}</span>
@else
    <span {{ $attributes->class('text-ink-500') }}>{{ $empty }}</span>
@endif
