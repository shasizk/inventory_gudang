<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- ui-demo=1: semua request AJAX dijawab simulator resources/js/lib/demo.js --}}
    <meta name="ui-demo" content="{{ config('app.ui_demo', app()->isLocal()) ? '1' : '0' }}">
    <meta name="user-role" content="{{ $currentRole ?? 'guest' }}">
    <meta name="door-status-url" content="{{ route('ajax.door.status') }}">

    <title>@yield('title', 'Dashboard') | {{ config('app.name', 'Smart Gudang') }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Barlow+Semi+Condensed:wght@500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body x-data="{ nav: false }" x-on:keydown.escape.window="nav = false">
    @include('layouts.partials.compat-notice')
    <a href="#konten" class="sr-only z-[80] rounded bg-ink-900 px-3 py-2 text-white focus:not-sr-only focus:fixed focus:top-3 focus:left-3">Lewati ke konten</a>

    <div class="flex min-h-dvh">
        @include('layouts.partials.sidebar')

        <div class="flex min-w-0 flex-1 flex-col">
            @include('layouts.partials.topbar')

            <main id="konten" class="mx-auto w-full max-w-[1480px] flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                @yield('content')
            </main>
        </div>
    </div>

    <x-toasts />

    {{-- Sesi login berakhir (401/419 dari AJAX): satu banner tetap, polling berhenti, bukan toast berulang --}}
    <div x-data x-show="$store.session.expired" x-cloak role="alertdialog" aria-labelledby="expired-title"
         class="fixed inset-x-0 bottom-0 z-[75] border-t-4 border-hazard-500 bg-ink-900 text-white">
        <div class="mx-auto flex max-w-[1480px] flex-wrap items-center gap-x-6 gap-y-3 px-4 py-4 sm:px-6 lg:px-8">
            <div class="min-w-0 flex-1">
                <p id="expired-title" class="font-semibold">Sesi login Anda berakhir</p>
                <p class="text-sm text-ink-300">Status pintu dan aktivitas berhenti diperbarui. Masuk lagi untuk melanjutkan; data yang sudah tersimpan tetap aman.</p>
            </div>
            <a href="{{ Route::has('login') ? route('login') : url('/') }}" class="btn btn-scan">Masuk lagi</a>
        </div>
    </div>
    {{-- Di dashboard tidak perlu: widget Status pintu sudah menampilkan verifikasi yang sama --}}
    @if (in_array($currentRole ?? 'guest', ['owner', 'admin'], true) && ! request()->routeIs('dashboard'))
        <x-door-verification-alert />
    @endif
    <x-confirm-delete />
    @stack('modals')

    {{-- Flash message dari controller: return back()->with('success', 'Kategori disimpan.') --}}
    @if (session('success'))
        <div x-data x-init="$store.toast.success(@js(session('success')))"></div>
    @endif
    @if (session('error'))
        <div x-data x-init="$store.toast.error('Tindakan gagal', @js(session('error')))"></div>
    @endif
    @if ($errors->any())
        <div x-data x-init="$store.toast.error('Periksa kembali isian form', @js($errors->first()))"></div>
    @endif

    @stack('scripts')
</body>
</html>
