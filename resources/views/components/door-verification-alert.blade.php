{{--
    Notifikasi melayang saat kartu Teknisi menunggu tap Admin.
    Sengaja bukan modal yang memblokir layar: Admin mungkin sedang mengisi form atau scan barang,
    dan tindakan yang diminta (tap kartu) terjadi di reader fisik, bukan di layar.
    Dipasang di layout, hanya dirender untuk peran owner/admin.
--}}
{{-- Otomatis mengecil setelah 8 detik agar tidak menutupi tombol (mis. Unduh di halaman Laporan); klik untuk membuka lagi --}}
<div x-data="{ minimized: false, timer: null }" x-cloak
     x-show="$store.door.pendingDoors.length"
     x-transition.opacity
     x-effect="
        if ($store.door.pendingDoors.length && !timer) { minimized = false; timer = setTimeout(() => (minimized = true), 8000) }
        if (!$store.door.pendingDoors.length && timer) { clearTimeout(timer); timer = null; minimized = false }
     "
     class="fixed right-3 bottom-3 z-[65]" x-bind:class="minimized ? 'left-auto w-auto' : 'left-3 sm:left-auto sm:w-[380px]'"
     role="region" aria-label="Verifikasi pintu yang menunggu">
    <template x-for="door in $store.door.pendingDoors" :key="door.id">
        <div class="mt-2 overflow-hidden rounded-lg border bg-white shadow-[0_16px_40px_-16px_rgb(14_26_38/0.55)]"
             x-bind:class="$store.door.phase(door) === 'timeout' ? 'border-bad-600' : 'border-hazard-500'">
            <div class="hazard-edge h-1.5" aria-hidden="true"></div>
            <div class="flex items-center gap-3" x-bind:class="minimized ? 'p-2 pr-1.5' : 'p-3.5'">
                <button type="button" class="shrink-0 rounded-full" x-on:click="minimized = !minimized" x-bind:aria-label="minimized ? 'Buka detail verifikasi' : 'Perkecil notifikasi'">
                    <x-countdown-ring seconds="$store.door.remaining(door)" progress="$store.door.progress(door, door.pending.timeout ?? 30)" :size="40" />
                </button>
                <div class="min-w-0 flex-1" x-show="!minimized">
                    <p class="font-semibold leading-snug" x-text="$store.door.phase(door) === 'timeout' ? 'Waktu verifikasi habis' : door.pending.technician + ' menunggu di ' + door.name"></p>
                    <p class="text-sm leading-snug text-ink-500" x-text="$store.door.phase(door) === 'timeout' ? 'Minta ' + door.pending.technician + ' tap kartu ulang.'                     : 'Minta Admin menempelkan kartu di reader pintu.'"></p>
                </div>
                <p class="min-w-0 truncate pr-1 text-sm font-semibold" x-show="minimized" x-text="door.short + ' menunggu'"></p>
                <button type="button" class="btn btn-ghost btn-icon shrink-0" x-on:click="minimized = !minimized" x-bind:aria-label="minimized ? 'Perbesar notifikasi' : 'Perkecil notifikasi'">
                    <x-icon name="menu" :size="16" />
                </button>
            </div>
        </div>
    </template>
    {{-- Pengumuman untuk screen reader, hanya saat verifikasi baru muncul (bukan tiap detik) --}}
    <p class="sr-only" aria-live="assertive" x-text="$store.door.pendingDoors.map(d => d.pending.technician + ' menunggu verifikasi di ' + d.name).join('. ')"></p>
</div>
