{{-- Layout untuk halaman tanpa login (masuk akun). Sengaja tanpa sidebar & tanpa status pintu: halaman publik tidak boleh membocorkan data. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="ui-demo" content="{{ config('app.ui_demo', app()->isLocal()) ? '1' : '0' }}">
    <meta name="robots" content="noindex">
    <title>@yield('title', 'Masuk') | {{ config('app.name', 'Smart Gudang') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@400;500;600;700&family=Barlow+Semi+Condensed:wght@500;600;700&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh">
    @include('layouts.partials.compat-notice')
    <main class="min-h-dvh">
        @yield('content')
    </main>
    <x-toasts />
    @if (session('success'))
        <div x-data x-init="$store.toast.success(@js(session('success')))"></div>
    @endif
</body>
</html>
