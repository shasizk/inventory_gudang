{{--
    Layout halaman error. SENGAJA tanpa @vite dan tanpa komponen Blade:
    halaman error harus tetap tampil walau aset belum di-build, database mati, atau komponen lain bermasalah.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') | {{ config('app.name', 'Smart Gudang') }}</title>
    <style>
        :root { color-scheme: light; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px;
               background: #eff2f3; color: #1b2a3a; font: 15px/1.5 'Barlow', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif; }
        .card { position: relative; width: 100%; max-width: 520px; background: #fff; border: 1px solid #cdd5db; border-radius: 10px; overflow: hidden; padding: 32px 32px 28px 40px; }
        .card::before { content: ''; position: absolute; inset: 0 auto 0 0; width: 8px;
                        background: repeating-linear-gradient(-45deg, #f2b705 0 9px, #1b2a3a 9px 18px); }
        .code { font: 600 13px/1 ui-monospace, Menlo, monospace; color: #53677b; letter-spacing: .02em; }
        h1 { margin: 10px 0 8px; font: 600 28px/1.15 'Barlow Semi Condensed', 'Barlow', system-ui, sans-serif; }
        p { margin: 0 0 8px; color: #3b5167; max-width: 60ch; }
        .actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 20px; }
        .btn { display: inline-flex; align-items: center; height: 40px; padding: 0 14px; border-radius: 6px; font-weight: 600; text-decoration: none; border: 1px solid #cdd5db; color: #1b2a3a; background: #fff; cursor: pointer; font-size: 15px; }
        .btn.primary { background: #1b2a3a; border-color: #1b2a3a; color: #fff; }
        .btn:focus-visible { outline: 2px solid #1d5fd1; outline-offset: 2px; }
    </style>
</head>
<body>
    <main class="card">
        <span class="code">Kode @yield('code')</span>
        <h1>@yield('title')</h1>
        @yield('message')
        <div class="actions">
            @yield('actions')
            <a class="btn" href="{{ url('/') }}">Ke dashboard</a>
        </div>
    </main>
</body>
</html>
