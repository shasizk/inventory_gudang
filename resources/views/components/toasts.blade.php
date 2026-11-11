{{-- Notifikasi global. Panggil dari Alpine: $store.toast.success('Judul', 'Pesan') / .error / .warning / .info --}}
<div x-data class="pointer-events-none fixed inset-x-0 top-[4.5rem] z-[70] flex flex-col items-center gap-2 px-3 sm:inset-x-auto sm:right-5 sm:items-end" aria-live="polite">
    <template x-for="t in $store.toast.items" :key="t.id">
        <div class="toast-in pointer-events-auto flex w-full max-w-sm gap-3 overflow-hidden rounded-lg border border-l-4 bg-white p-3.5 shadow-[0_12px_32px_-12px_rgb(14_26_38/0.45)]"
             x-bind:class="{
                'border-ok-600/30 border-l-ok-600': t.type === 'success',
                'border-bad-600/30 border-l-bad-600': t.type === 'error',
                'border-warn-600/30 border-l-hazard-500': t.type === 'warning',
                'border-in-600/30 border-l-in-600': t.type === 'info',
             }"
             x-bind:role="t.type === 'error' ? 'alert' : 'status'">
            <span class="mt-0.5" x-bind:class="{ 'text-ok-600': t.type === 'success', 'text-bad-600': t.type === 'error', 'text-warn-600': t.type === 'warning', 'text-in-600': t.type === 'info' }">
                <template x-if="t.type === 'success'"><x-icon name="check" /></template>
                <template x-if="t.type === 'error'"><x-icon name="x" /></template>
                <template x-if="t.type === 'warning'"><x-icon name="alert" /></template>
                <template x-if="t.type === 'info'"><x-icon name="info" /></template>
            </span>
            <div class="min-w-0 flex-1">
                <p class="font-semibold leading-snug"><span x-text="t.title"></span> <span x-show="t.count > 1" class="num text-ink-500" x-text="'×' + t.count"></span></p>
                <p class="mt-0.5 text-sm leading-snug break-words text-ink-500" x-show="t.message" x-text="t.message"></p>
            </div>
            <button type="button" class="-m-1 h-7 w-7 shrink-0 rounded text-ink-400 hover:bg-concrete-100 hover:text-ink-900" x-on:click="$store.toast.dismiss(t.id)" aria-label="Tutup notifikasi">
                <x-icon name="x" :size="16" class="mx-auto" />
            </button>
        </div>
    </template>
</div>
