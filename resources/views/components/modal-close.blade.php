{{-- Tombol X untuk header modal kustom: <x-modal-close name="kategori-form" /> --}}
@props(['name'])
<button type="button" class="btn btn-ghost btn-icon -mr-2 shrink-0" x-on:click="$dispatch('close-modal', @js($name))" aria-label="Tutup">
    <x-icon name="x" />
</button>
