{{-- Kerangka dua kolom untuk halaman masuk / daftar / OTP --}}
@props(['title', 'subtitle' => null, 'max' => 'max-w-[400px]'])
<div class="grid min-h-dvh w-full lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">
    <aside class="relative hidden flex-col justify-between overflow-hidden bg-ink-900 px-12 py-10 text-white lg:flex" aria-hidden="true">
        <div class="flex items-center gap-3">
            <span class="grid size-10 place-items-center rounded-md bg-hazard-500 text-ink-950"><x-icon name="lock" :size="20" /></span>
            <span class="font-display text-xl font-semibold">{{ config('app.name', 'Smart Gudang') }}</span>
        </div>
        <div class="mx-auto w-full max-w-[460px]">
            <x-illustration.door-reader />
            <p class="mt-8 max-w-[24ch] font-display text-[30px] leading-[1.15] font-semibold">Gudang dan ruang ICT dalam satu sistem</p>
            <p class="mt-3 max-w-[46ch] text-ink-300">Akses pintu, stok barang, dan absensi tercatat otomatis dari setiap tap kartu dan scan barcode.</p>
        </div>
        <p class="text-sm text-ink-400">&copy; {{ now()->year }} {{ config('app.name', 'Smart Gudang') }}</p>
    </aside>

    <section class="relative flex flex-col justify-center bg-concrete-50 px-5 py-10 sm:px-10 lg:bg-white" aria-labelledby="auth-title">
        <div class="hazard-edge absolute inset-x-0 top-0 h-1.5 lg:hidden" aria-hidden="true"></div>
        <div class="mx-auto w-full {{ $max }}">
            <div class="mb-8 flex items-center gap-3 lg:hidden">
                <span class="grid size-10 place-items-center rounded-md bg-ink-900 text-hazard-500" aria-hidden="true"><x-icon name="lock" :size="20" /></span>
                <div class="leading-tight">
                    <p class="font-display text-xl font-semibold">{{ config('app.name', 'Smart Gudang') }}</p>
                    <p class="text-sm text-ink-500">Smart Lock & inventaris gudang</p>
                </div>
            </div>

            <h1 id="auth-title" class="font-display text-[30px] leading-tight font-semibold">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1.5 text-ink-500">{{ $subtitle }}</p>
            @endif
            <div class="mt-7">
                {{ $slot }}
            </div>
        </div>
    </section>
</div>