@php
    $role = $currentRole ?? 'guest';
    // [label, icon, nama route, pola routeIs untuk status aktif]
    $nav = [
        ['items' => [['Dashboard', 'home', 'dashboard', 'dashboard']]],
        ['group' => 'Barang masuk', 'items' => [
            ['Kategori barang', 'tag', 'kategori.index', 'kategori.*'],
            ['Serial number', 'barcode', 'serial.index', 'serial.*'],
            ['Barang masuk', 'arrow-in', 'barang-masuk.index', 'barang-masuk.*'],
        ]],
        ['group' => 'Barang keluar', 'items' => [
            ['Stok barang', 'boxes', 'stok.index', 'stok.*'],
            ['Barang keluar', 'arrow-out', 'barang-keluar.index', 'barang-keluar.*'],
        ]],
        ['group' => 'Absensi', 'items' => [
            ['Absensi Teknisi & NOC', 'clock', 'absensi.index', 'absensi.*'],
        ]],
        ['group' => 'User management', 'roles' => ['owner', 'admin'], 'items' => [
            ['Kartu RFID', 'id-card', 'cards.index', 'cards.*'],
            ['Akun web', 'users', 'users.index', 'users.*'],
        ]],
        ['group' => 'Laporan', 'roles' => ['owner', 'admin'], 'items' => [
            ['Laporan & ekspor', 'file', 'laporan.index', 'laporan.*'],
        ]],
        ['group' => 'History', 'items' => [
            ['Riwayat pintu', 'door', 'history.door', 'history.door'],
            ['Riwayat barang', 'history', 'history.barang', 'history.barang'],
            // Audit log berisi IP & aktivitas semua akun: khusus Owner/Admin
            ['Audit log', 'shield', 'history.audit', 'history.audit', ['owner', 'admin']],
        ]],
    ];
    $scanUrl = $role === 'owner' ? route('barang-masuk.index') . '#scan' : route('barang-keluar.index') . '#scan';
    $canScan = in_array($role, ['owner', 'admin', 'teknisi', 'noc'], true);
@endphp

{{-- Backdrop menu di HP --}}
<div x-show="nav" x-transition.opacity x-cloak class="fixed inset-0 z-40 bg-ink-950/55 lg:hidden" x-on:click="nav = false"></div>

<aside class="fixed inset-y-0 left-0 z-50 flex w-[268px] -translate-x-full flex-col bg-ink-900 text-ink-300 transition-transform duration-200 lg:sticky lg:top-0 lg:h-dvh lg:translate-x-0"
       x-bind:class="nav && 'max-lg:translate-x-0'"
       aria-label="Navigasi utama">

    <div class="flex h-16 shrink-0 items-center gap-3 border-b border-white/8 px-5">
        <span class="grid size-9 place-items-center rounded-md bg-hazard-500 text-ink-950" aria-hidden="true">
            <x-icon name="lock" :size="20" />
        </span>
        <div class="min-w-0 leading-tight">
            <p class="truncate font-display text-lg font-semibold text-white">{{ config('app.name', 'Smart Gudang') }}</p>
            <p class="truncate text-[13px] text-ink-400">Smart Lock & inventaris</p>
        </div>
        <button type="button" class="ml-auto rounded p-1 text-ink-400 hover:text-white lg:hidden" x-on:click="nav = false" aria-label="Tutup menu">
            <x-icon name="x" />
        </button>
    </div>

    {{-- Posisi scroll disimpan & item aktif selalu terlihat setelah pindah halaman --}}
    <nav class="sidebar-scroll flex-1 overflow-y-auto overscroll-contain px-3 pt-2 pb-4"
         x-data
         x-init="
            const nav = $el;
            try { nav.scrollTop = Number(sessionStorage.getItem('sidebar-scroll') || 0) } catch (e) {}
            const active = $el.querySelector('[aria-current=page]');
            if (active) {
                const a = active.getBoundingClientRect(), n = $el.getBoundingClientRect();
                // Geser hanya area menu (bukan seluruh halaman) agar menu aktif berada di tengah
                if (a.top < n.top || a.bottom > n.bottom) nav.scrollTop += a.top - n.top - (n.height - a.height) / 2;
            }
         "
         x-on:scroll.debounce.150ms="(() => { try { sessionStorage.setItem('sidebar-scroll', $el.scrollTop) } catch (e) {} })()">
        @foreach ($nav as $section)
            @if (isset($section['roles']) && ! in_array($role, $section['roles'], true))
                @continue
            @endif
            @isset($section['group'])
                <p class="mt-4 mb-1 px-3 text-[13px] font-semibold text-ink-400">{{ $section['group'] }}</p>
            @endisset
            <ul class="space-y-0.5">
                @foreach ($section['items'] as $item)
                    @php [$label, $icon, $routeName, $pattern] = $item; $itemRoles = $item[4] ?? null; @endphp
                    @if ($itemRoles && ! in_array($role, $itemRoles, true))
                        @continue
                    @endif
                    <li>
                        <a href="{{ route($routeName) }}" class="nav-link" @if (request()->routeIs($pattern)) aria-current="page" @endif>
                            <x-icon :name="$icon" :size="19" />
                            <span class="truncate">{{ $label }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endforeach
    </nav>

    {{-- Pintasan scan: tujuan menyesuaikan hak akses (Owner = barang masuk, lainnya = barang keluar) --}}
    @if ($canScan)
        <div class="shrink-0 border-t border-white/8 p-3">
            <a href="{{ $scanUrl }}" class="btn btn-scan h-11 w-full">
                <x-icon name="scan" />
                {{ $role === 'owner' ? 'Scan barang masuk' : 'Scan barang keluar' }}
            </a>
        </div>
    @endif
</aside>
