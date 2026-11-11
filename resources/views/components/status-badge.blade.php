{{--
    <x-status-badge status="..." />  Satu sumber kebenaran untuk warna & label status.

    Status arah   : in, out
    Status hasil  : success, warning, danger, pending, neutral
    Alias domain  : granted, denied (akses pintu) | aman, menipis, habis (stok)
                    gudang, ict (pintu) | owner, admin, teknisi, noc (peran)
    Slot (opsional) menimpa label bawaan: <x-status-badge status="warning">Menipis</x-status-badge>
    title (opsional) untuk keterangan tambahan saat hover/fokus, mis. "Minimum 5 unit".
--}}
@props(['status' => 'neutral', 'icon' => true, 'title' => null])
@php
    $map = [
        // [kelas, label bawaan, ikon]
        'in'       => ['badge-in', 'IN', 'arrow-in'],
        'out'      => ['badge-out', 'OUT', 'arrow-out'],
        'success'  => ['badge-ok', 'Berhasil', 'check'],
        'warning'  => ['badge-warn', 'Perlu dicek', 'alert'],
        'danger'   => ['badge-bad', 'Ditolak', 'x'],
        'pending'  => ['badge-pending', 'Menunggu', 'clock'],
        'neutral'  => ['badge-neutral', '', null],

        'granted'  => ['badge-ok', 'Diizinkan', 'check'],
        'denied'   => ['badge-bad', 'Ditolak', 'x'],
        'aman'     => ['badge-ok', 'Aman', null],
        'menipis'  => ['badge-warn', 'Menipis', 'alert'],
        'habis'    => ['badge-bad', 'Habis', 'x'],

        'gudang'   => ['badge-dark', 'Gudang', 'door'],
        'ict'      => ['badge-outline', 'Ruang ICT', 'server'],

        'owner'    => ['badge-dark', 'Owner', null],
        'admin'    => ['badge-strong', 'Admin', null],
        'teknisi'  => ['badge-neutral', 'Teknisi', null],
        'noc'      => ['badge-neutral', 'NOC', null],
    ];
    [$class, $default, $glyph] = $map[strtolower((string) $status)] ?? $map['neutral'];
@endphp
<span {{ $attributes->class(['badge', $class]) }} @if ($title) title="{{ $title }}" @endif>
    @if ($icon && $glyph)<x-icon :name="$glyph" :size="14" />@endif
    <span>{{ $slot->isEmpty() ? $default : $slot }}</span>
    @if ($title)<span class="sr-only">, {{ $title }}</span>@endif
</span>
