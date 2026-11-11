{{--
    Stasiun scan barcode (logika: resources/js/components/scan-station.js).
    <x-scan-station mode="in"  :endpoint="route('ajax.barang.lookup')" />      Owner: membuka form barang masuk
    <x-scan-station mode="out" :endpoint="route('ajax.barang.keluar.scan')" />  Non-owner: langsung potong stok
--}}
@props(['mode' => 'out', 'endpoint'])
@php $isIn = $mode === 'in'; @endphp
<section id="scan" {{ $attributes->class('panel relative overflow-hidden') }}
         x-data="scanStation({ mode: @js($mode), endpoint: @js($endpoint) })"
         x-on:click="$event.target.closest('button, a, input, select, label, textarea') || focus()"
         aria-labelledby="scan-title">
    <div class="hazard-edge absolute inset-y-0 left-0 w-2" aria-hidden="true"></div>

    <div class="grid gap-x-8 gap-y-5 py-5 pr-5 pl-7 lg:grid-cols-[minmax(0,1fr)_300px]">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2.5">
                <h2 id="scan-title" class="font-display text-[22px] leading-tight font-semibold">{{ $isIn ? 'Scan barang masuk' : 'Scan barang keluar' }}</h2>
                <x-status-badge :status="$isIn ? 'in' : 'out'" />
            </div>
            <p class="mt-1 max-w-[62ch] text-ink-500">
                {{ $isIn
                    ? 'Data barang terisi otomatis bila barang sudah pernah terdaftar.'
                    : 'Setiap scan langsung tercatat atas nama akun Anda dan memotong stok.' }}
            </p>

            <form class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-end" x-on:submit.prevent="enqueue(code, source()); code = ''">
                <label class="scan-field min-w-0 flex-1">
                    <span class="sr-only">Kode barcode atau serial number</span>
                    <x-icon name="scan" :size="22" class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-ink-400" />
                    {{-- Sengaja TIDAK disabled saat loading: ketikan scanner berikutnya tetap tertangkap lalu diantre --}}
                    <input x-ref="input" x-model="code" x-on:keydown="onInputKey($event)"
                           type="text" inputmode="text" autocomplete="off" autocapitalize="characters" spellcheck="false" enterkeyhint="send"
                           maxlength="80" aria-describedby="scan-status"
                           class="input h-14 pl-12 font-code text-lg tracking-wide uppercase placeholder:font-sans placeholder:text-base placeholder:tracking-normal placeholder:normal-case"
                           placeholder="{{ $isIn ? 'Scan atau ketik kode barang' : 'Scan atau ketik kode' }}">
                    <span class="scan-laser" aria-hidden="true"></span>
                </label>
                @unless ($isIn)
                    <label class="flex items-center gap-2 sm:block sm:w-24">
                        <span class="text-sm font-semibold text-ink-700 sm:mb-1 sm:block sm:text-[13px] sm:font-medium sm:text-ink-500">Jumlah</span>
                        <input type="number" min="1" max="999" step="1" inputmode="numeric" x-model.number="qty" x-on:blur="qty = clampQty(qty)"
                               x-on:keydown.enter.prevent="$refs.input.focus()"
                               class="input num h-14 w-24 text-center text-lg" aria-label="Jumlah per scan">
                    </label>
                @endunless
                <button type="submit" class="btn btn-scan h-14 px-6 text-base" x-bind:disabled="!code.trim()">
                    <span class="spinner" x-show="busy" x-cloak></span>
                    <span>{{ $isIn ? 'Cari barang' : 'Catat keluar' }}</span>
                </button>
            </form>
            <p class="hint">Scanner bisa langsung dipakai tanpa mengklik kolom. Ketik manual lalu tekan Enter.</p>

            <div id="scan-status" class="mt-3 flex min-h-12 items-start gap-2.5 rounded-md px-3.5 py-3" x-bind:class="statusClass" role="status" aria-live="polite">
                <span class="mt-0.5">
                    <template x-if="state === 'loading' || state === 'reading'"><span class="spinner block"></span></template>
                    <template x-if="state === 'success'"><x-icon name="check" :size="18" /></template>
                    <template x-if="state === 'warning'"><x-icon name="alert" :size="18" /></template>
                    <template x-if="state === 'error'"><x-icon name="x" :size="18" /></template>
                    <template x-if="state === 'idle'"><x-icon name="scan" :size="18" /></template>
                </span>
                <p class="min-w-0 flex-1 font-medium leading-snug break-words" x-text="state === 'reading' ? 'Membaca barcode' : message"></p>
                <span x-show="queue.length" x-cloak class="badge badge-pending shrink-0" x-text="queue.length + ' antre'"></span>
            </div>

            <template x-if="demo">
                <div class="mt-3 flex flex-wrap items-center gap-1.5 text-sm text-ink-500">
                    <span>Kode contoh mode demo:</span>
                    <template x-for="c in demoCodes" :key="c">
                        <button type="button" class="code rounded border border-dashed border-concrete-300 px-1.5 py-0.5 hover:border-ink-400 hover:bg-concrete-50" x-on:click="useDemoCode(c)" x-text="c"></button>
                    </template>
                </div>
            </template>
        </div>

        <aside class="min-w-0 border-t border-concrete-200 pt-4 lg:border-t-0 lg:border-l lg:pt-0 lg:pl-6" aria-label="Riwayat scan sesi ini">
            <h3 class="text-sm font-semibold text-ink-700">Scan terakhir di sesi ini</h3>
            <p class="mt-2 text-sm text-ink-500" x-show="!recent.length">Belum ada scan. Hasil setiap scan muncul di sini agar mudah dicek ulang.</p>
            <ul class="mt-2 divide-y divide-concrete-200" x-show="recent.length" x-cloak>
                <template x-for="r in recent" :key="r.id">
                    <li class="flex items-center gap-3 py-2">
                        <span class="size-2 shrink-0 rounded-full" x-bind:class="{ 'bg-ok-600': r.status === 'success', 'bg-hazard-500': r.status === 'warning', 'bg-bad-600': r.status === 'error' }"></span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold" x-text="r.label" x-bind:title="r.label"></p>
                            <p class="code truncate text-ink-500" x-text="r.code" x-bind:title="r.code"></p>
                        </div>
                        <div class="shrink-0 text-right text-[13px] text-ink-500">
                            <p class="num" x-text="r.time"></p>
                            <p class="num" x-show="r.note" x-text="r.note"></p>
                        </div>
                    </li>
                </template>
            </ul>
        </aside>
    </div>
</section>
