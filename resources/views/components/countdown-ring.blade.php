{{--
    Cincin hitung mundur. Nilai dari ekspresi Alpine:
    <x-countdown-ring seconds="$store.door.remaining(door)" progress="$store.door.progress(door)" />
    Berubah merah saat sisa ≤ 10 detik. Teks angka tetap ada, jadi tidak bergantung pada warna.
    Sengaja TANPA transisi CSS: animasi stroke yang terus berjalan menurunkan kelancaran halaman
    dari 60 ke ±20 FPS di komputer tanpa akselerasi grafis (terukur di v1.3). Cincin bergerak per detik.
--}}
@props(['seconds', 'progress', 'size' => 48])
<span {{ $attributes->class('relative inline-grid shrink-0 place-items-center') }} style="width: {{ $size }}px; height: {{ $size }}px"
      role="timer" x-bind:aria-label="({{ $seconds }}) + ' detik tersisa'">
    <svg viewBox="0 0 40 40" class="absolute inset-0 -rotate-90" aria-hidden="true">
        <circle cx="20" cy="20" r="16" fill="none" stroke="currentColor" stroke-width="4" class="text-concrete-200" />
        <circle cx="20" cy="20" r="16" fill="none" stroke-width="4" stroke-linecap="round"
                stroke-dasharray="100.53" x-bind:stroke-dashoffset="100.53 * (1 - ({{ $progress }}))"
                x-bind:class="({{ $seconds }}) <= 10 ? 'stroke-bad-600' : 'stroke-hazard-500'" />
    </svg>
    <span class="num relative font-display text-[15px] leading-none font-bold" x-bind:class="({{ $seconds }}) <= 10 && 'text-bad-700'" x-text="{{ $seconds }}"></span>
</span>
