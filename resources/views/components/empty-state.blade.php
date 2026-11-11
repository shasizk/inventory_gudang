@props(['icon' => 'boxes', 'title' => 'Belum ada data', 'text' => null])
<div {{ $attributes->class('flex flex-col items-center px-6 py-12 text-center') }}>
    <span class="grid size-12 place-items-center rounded-full border border-dashed border-concrete-300 text-ink-400">
        <x-icon :name="$icon" :size="22" />
    </span>
    <p class="mt-3 font-display text-lg font-semibold text-ink-900">{{ $title }}</p>
    @if ($text)<p class="mt-1 max-w-[46ch] text-ink-500">{{ $text }}</p>@endif
    @if ($slot->isNotEmpty())<div class="mt-4 flex flex-wrap justify-center gap-2">{{ $slot }}</div>@endif
</div>
