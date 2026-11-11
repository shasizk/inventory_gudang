{{--
    Satu modal konfirmasi hapus untuk seluruh aplikasi (dipasang di layout).
    Pemicu: $dispatch('confirm-delete', { url: '{{ route(...destroy, id) }}', label: 'Nama data', note: 'opsional' })
--}}
<x-modal name="confirm-delete" size="sm">
    <form method="POST" x-data="{ url: '', label: '', note: '', busy: false }"
          x-on:confirm-delete.window="url = $event.detail.url; label = $event.detail.label; note = $event.detail.note ?? ''; busy = false; $dispatch('open-modal', 'confirm-delete')"
          x-bind:action="url" x-on:submit="busy ? $event.preventDefault() : (busy = true)">
        @csrf
        @method('DELETE')
        <div class="flex gap-4 px-5 pt-5">
            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-bad-50 text-bad-600"><x-icon name="trash" /></span>
            <div>
                <h2 class="modal-title">Hapus data ini?</h2>
                <p class="mt-1.5 text-ink-700"><span class="font-semibold" x-text="label"></span> akan dihapus permanen dan tidak bisa dikembalikan.</p>
                <p class="mt-1.5 text-sm text-ink-500" x-show="note" x-text="note"></p>
            </div>
        </div>
        <div class="modal-foot mt-5">
            <x-button variant="secondary" x-on:click="$dispatch('close-modal', 'confirm-delete')">Batal</x-button>
            <x-button type="submit" variant="danger" icon="trash" loading="busy">Hapus permanen</x-button>
        </div>
    </form>
</x-modal>
