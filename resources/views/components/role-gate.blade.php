{{--
    Penjaga tampilan berbasis peran. Hanya kenyamanan UI: backend tetap wajib memakai middleware/policy.

    <x-role-gate :allow="['owner', 'admin']" title="Audit log khusus Owner & Admin" text="..." :back="route('dashboard')">
        isi halaman
    </x-role-gate>

    mode="locked" (bawaan) : isi diganti panel terkunci
    mode="readonly"        : isi tetap tampil, tapi semua input/tombol di dalamnya dinonaktifkan (fieldset disabled)
--}}
@props(['allow' => [], 'title' => 'Halaman ini dibatasi', 'text' => null, 'back' => null, 'backLabel' => 'Kembali ke dashboard', 'mode' => 'locked'])
@php $allowed = in_array($currentRole ?? 'guest', (array) $allow, true); @endphp

@if ($allowed)
    {{ $slot }}
@elseif ($mode === 'readonly')
    <div {{ $attributes->class('relative') }}>
        <div class="mb-3 flex items-start gap-2.5 rounded-md border border-concrete-300 bg-concrete-50 px-3.5 py-2.5 text-sm text-ink-700" role="note">
            <x-icon name="lock" :size="18" class="mt-0.5 text-ink-500" />
            <span><span class="font-semibold">{{ $title }}.</span> {{ $text }}</span>
        </div>
        <fieldset disabled class="contents opacity-60">{{ $slot }}</fieldset>
    </div>
@else
    <section {{ $attributes->class('panel flex flex-col items-center px-6 py-14 text-center') }} aria-labelledby="gate-title">
        <span class="grid size-12 place-items-center rounded-full bg-ink-900 text-hazard-500"><x-icon name="lock" :size="22" /></span>
        <h2 id="gate-title" class="mt-4 font-display text-xl font-semibold">{{ $title }}</h2>
        @if ($text)<p class="mt-1.5 max-w-[52ch] text-ink-500">{{ $text }}</p>@endif
        <p class="mt-1 text-sm text-ink-500">Akun Anda: <x-status-badge :status="$currentRole ?? 'neutral'" /></p>
        <a href="{{ $back ?? route('dashboard') }}" class="btn btn-secondary mt-5">{{ $backLabel }}</a>
    </section>
@endif
