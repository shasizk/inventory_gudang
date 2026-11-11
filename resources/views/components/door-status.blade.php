{{-- Widget status Smart Lock di dashboard (data: $store.door, polling /ajax/door/status tiap 5 detik) --}}
<section {{ $attributes->class('panel overflow-hidden') }} x-data aria-labelledby="door-title">
    <header class="panel-head">
        <div>
            <h2 id="door-title" class="panel-title">Status pintu</h2>
            <p class="mt-0.5 text-sm text-ink-500">Koneksi perangkat dan verifikasi yang sedang berjalan</p>
        </div>
        <p class="flex items-center gap-1.5 text-sm text-ink-500" x-show="$store.door.updatedAt" x-cloak>
            <x-icon name="refresh" :size="15" />
            <span>Diperbarui <span x-text="$store.door.updatedLabel"></span></span>
        </p>
    </header>

    <div class="grid sm:grid-cols-2" x-show="$store.door.loading && !$store.door.doors.length" aria-hidden="true">
        @for ($i = 0; $i < 2; $i++)
            <div class="space-y-3 p-5 {{ $i ? 'border-concrete-200 sm:border-l' : '' }}">
                <div class="skeleton h-6 w-36"></div><div class="skeleton h-4 w-48"></div><div class="skeleton h-10 w-full"></div>
            </div>
        @endfor
    </div>

    <div class="flex items-start gap-3 bg-bad-50 px-5 py-4 text-bad-700" x-show="!$store.door.loading && $store.door.error && !$store.door.doors.length" x-cloak role="alert">
        <x-icon name="alert" class="mt-0.5" />
        <div>
            <p class="font-semibold">Status pintu tidak dapat dimuat</p>
            <p class="text-sm" x-text="$store.door.error + ' Halaman mencoba lagi otomatis tiap 5 detik.'"></p>
        </div>
    </div>

    <div class="grid divide-y divide-concrete-200 sm:grid-cols-2 sm:divide-x sm:divide-y-0" x-show="$store.door.doors.length" x-cloak>
        <template x-for="door in $store.door.doors" :key="door.id">
            {{-- @container: susunan langkah verifikasi mengikuti lebar kartu pintu, bukan lebar layar --}}
            <article class="@container flex flex-col p-5" x-bind:class="!door.online && 'bg-bad-50/40'">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <h3 class="font-display text-[22px] leading-tight font-semibold" x-text="door.name"></h3>
                        <p class="mt-1 flex items-center gap-1.5 text-sm font-medium" x-bind:class="door.online ? 'text-ok-700' : 'text-bad-700'">
                            <span class="size-2 shrink-0 rounded-full" x-bind:class="door.online ? 'bg-ok-600' : 'bg-bad-600'"></span>
                            <span x-text="door.online ? 'Perangkat terhubung' : 'Perangkat terputus, terakhir aktif ' + $store.door.seen(door)"></span>
                        </p>
                    </div>
                    <div class="flex h-[60px] w-[92px] shrink-0 flex-col items-center justify-center gap-1 rounded-md text-sm font-semibold"
                         x-bind:class="!door.online ? 'bg-concrete-200 text-ink-500' : (door.locked ? 'bg-ink-900 text-white' : 'bg-hazard-500 text-ink-950')">
                        <span x-show="door.locked"><x-icon name="lock" :size="22" /></span>
                        <span x-show="!door.locked"><x-icon name="unlock" :size="22" /></span>
                        <span x-text="door.locked ? 'Terkunci' : 'Terbuka'"></span>
                    </div>
                </div>

                {{-- Verifikasi 2 langkah --}}
                <template x-if="door.pending">
                    <div class="mt-4 overflow-hidden rounded-md border"
                         x-bind:class="$store.door.phase(door) === 'timeout' ? 'border-bad-600 bg-bad-50' : 'border-hazard-500 bg-hazard-300/20'">
                        <div class="hazard-edge h-1.5" aria-hidden="true"></div>
                        <div class="p-3.5">
                            <div class="flex items-center gap-3">
                                <x-countdown-ring seconds="$store.door.remaining(door)" progress="$store.door.progress(door, door.pending.timeout ?? 30)" />
                                <div class="min-w-0">
                                    <p class="font-semibold" x-text="$store.door.phase(door) === 'timeout' ? 'Waktu verifikasi habis' : 'Verifikasi 2 langkah berjalan'"></p>
                                    <p class="text-sm text-ink-700" x-text="$store.door.phase(door) === 'timeout'
                                        ? door.pending.technician + ' perlu tap kartu ulang di pintu.'
                                        : 'Admin atau Owner tempelkan kartu di reader ' + door.name + '.'"></p>
                                </div>
                            </div>
                            <ol class="mt-3 grid gap-2 text-sm @lg:grid-cols-3" aria-label="Langkah verifikasi">
                                <li class="flex gap-2 rounded bg-white/80 px-2.5 py-2">
                                    <span class="grid size-5 shrink-0 place-items-center rounded-full bg-ok-600 text-white"><x-icon name="check" :size="12" /></span>
                                    <span class="min-w-0"><span class="block truncate font-semibold" x-text="door.pending.technician"></span><span class="text-ink-500" x-text="door.pending.role + ' sudah tap'"></span></span>
                                </li>
                                <li class="flex gap-2 rounded bg-white px-2.5 py-2 ring-2" x-bind:class="$store.door.phase(door) === 'timeout' ? 'ring-bad-600' : 'ring-hazard-500'" aria-current="step">
                                    <span class="grid size-5 shrink-0 place-items-center rounded-full bg-ink-900 text-[11px] font-bold text-white">2</span>
                                    <span><span class="block font-semibold">Admin/Owner</span><span class="text-ink-500" x-text="$store.door.phase(door) === 'timeout' ? 'Tidak ada tap' : 'Menunggu tap kartu'"></span></span>
                                </li>
                                <li class="flex gap-2 rounded px-2.5 py-2 text-ink-500">
                                    <span class="grid size-5 shrink-0 place-items-center rounded-full border border-ink-300 text-[11px] font-bold">3</span>
                                    <span><span class="block font-semibold">Pintu terbuka</span>Otomatis setelah langkah 2</span>
                                </li>
                            </ol>
                        </div>
                    </div>
                </template>

                {{-- Umpan balik singkat setelah verifikasi selesai --}}
                <template x-if="!door.pending && $store.door.events[door.id]">
                    <p class="mt-4 flex items-center gap-2 rounded-md px-3 py-2.5 text-sm font-medium"
                       x-bind:class="$store.door.events[door.id].type === 'opened' ? 'bg-ok-50 text-ok-700' : 'bg-bad-50 text-bad-700'" role="status">
                        <span x-text="$store.door.events[door.id].type === 'opened'
                            ? 'Verifikasi berhasil. Pintu dibuka untuk ' + $store.door.events[door.id].technician + '.'
                            : 'Verifikasi gagal: tidak ada tap Admin/Owner dalam 30 detik.'"></span>
                    </p>
                </template>

                <p class="mt-4 border-t border-concrete-200 pt-3 text-sm text-ink-500" x-show="!door.pending && !$store.door.events[door.id]">
                    <span class="font-semibold text-ink-700">Terakhir:</span> <span x-text="door.last_event"></span>
                </p>
            </article>
        </template>
    </div>
</section>
