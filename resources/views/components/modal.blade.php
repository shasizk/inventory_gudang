{{--
    <x-modal name="kategori-form" size="sm|md|lg|xl">
        isi modal (pakai .modal-head / .modal-body / .modal-foot)
    </x-modal>

    Buka  : $dispatch('open-modal', 'nama')  atau  $dispatch('open-modal', { name: 'nama', mode, data })
    Tutup : $dispatch('close-modal', 'nama'), tombol Esc, atau klik backdrop.
    Di HP modal tampil sebagai bottom sheet agar mudah dijangkau ibu jari.
--}}
@props(['name', 'size' => 'md', 'title' => null, 'static' => false])
@php
    $width = ['sm' => 'sm:max-w-md', 'md' => 'sm:max-w-xl', 'lg' => 'sm:max-w-3xl', 'xl' => 'sm:max-w-5xl'][$size] ?? 'sm:max-w-xl';
@endphp
<div
    x-data="{
        show: false,
        matches(detail) { return (typeof detail === 'string' ? detail : detail?.name) === @js($name) },
    }"
    x-on:open-modal.window="matches($event.detail) && (show = true)"
    x-on:close-modal.window="matches($event.detail) && (show = false)"
    x-on:keydown.escape.window="show && (show = false, $dispatch('close-modal', @js($name)))"
    x-bind:data-modal-open="show || null"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-6"
    role="dialog"
    aria-modal="true"
    @if ($title) aria-label="{{ $title }}" @endif
>
    <div x-show="show" x-transition.opacity.duration.150ms class="absolute inset-0 bg-ink-950/60"
         @unless ($static) x-on:click="show = false; $dispatch('close-modal', @js($name))" @endunless></div>

    <div x-show="show"
         x-trap.inert.noscroll="show"
         x-transition:enter="transition duration-200 ease-out"
         x-transition:enter-start="translate-y-4 opacity-0 sm:translate-y-2"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition duration-120 ease-in"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="translate-y-2 opacity-0"
         {{ $attributes->class(['relative flex max-h-[92dvh] w-full flex-col overflow-hidden rounded-t-xl bg-white shadow-[0_24px_60px_-20px_rgb(14_26_38/0.55)] sm:rounded-xl', $width]) }}>
        @if ($title)
            <header class="modal-head">
                <h2 class="modal-title">{{ $title }}</h2>
                <button type="button" class="btn btn-ghost btn-icon -mr-2" x-on:click="show = false; $dispatch('close-modal', @js($name))" aria-label="Tutup">
                    <x-icon name="x" />
                </button>
            </header>
        @endif
        <div class="min-h-0 flex-1 overflow-y-auto">
            {{ $slot }}
        </div>
    </div>
</div>
