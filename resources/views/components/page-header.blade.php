@props(['title', 'description' => null])
<div {{ $attributes->class('mb-6 flex flex-wrap items-end justify-between gap-x-6 gap-y-4') }}>
    <div class="min-w-0">
        <h1 class="font-display text-[28px] leading-[1.15] font-semibold tracking-[-0.01em] text-ink-900 sm:text-[32px]">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1.5 max-w-[68ch] text-ink-500">{{ $description }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
    @endisset
</div>
