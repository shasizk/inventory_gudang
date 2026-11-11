@props(['title' => null, 'description' => null])
<section {{ $attributes->class('panel') }}>
    @if ($title || isset($actions))
        <header class="panel-head">
            <div class="min-w-0">
                @if ($title)<h2 class="panel-title">{{ $title }}</h2>@endif
                @if ($description)<p class="mt-0.5 text-sm text-ink-500">{{ $description }}</p>@endif
            </div>
            @isset($actions)<div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>@endisset
        </header>
    @endif
    {{ $slot }}
</section>
