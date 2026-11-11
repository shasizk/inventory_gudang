{{-- Nama + peran dengan susunan yang sama di semua tabel: <x-cell.person :name="$row->user" :role="$row->role" /> --}}
@props(['name' => null, 'role' => null, 'note' => null])
@php $label = filled($name) ? $name : 'Tidak diketahui'; @endphp
<div {{ $attributes->class('flex min-w-0 flex-col items-start gap-1') }}>
    <span @class(['max-w-[24ch] truncate font-semibold', 'text-ink-500' => blank($name)]) title="{{ $label }}">{{ $label }}</span>
    @if (filled($role))<x-status-badge :status="$role" />@endif
    @if (filled($note))<span class="text-[13px] text-ink-500">{{ $note }}</span>@endif
</div>
