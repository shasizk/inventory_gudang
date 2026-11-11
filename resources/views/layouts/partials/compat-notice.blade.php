{{--
    Peringatan bila JavaScript mati atau browser terlalu lama untuk Tailwind v4.
    Sengaja memakai style inline (bukan kelas Tailwind): tetap terbaca walau CSS aplikasi gagal dimuat.
    Batas minimum: Chrome/Edge 111, Firefox 128, Safari 16.4.
--}}
<noscript>
    <div style="position:relative;z-index:90;background:#1b2a3a;color:#fff;padding:12px 16px;font:15px/1.4 system-ui,sans-serif;border-bottom:4px solid #f2b705">
        <strong>JavaScript dinonaktifkan.</strong> Scan barang, status pintu, dan form tidak akan berfungsi. Aktifkan JavaScript di pengaturan browser lalu muat ulang halaman.
    </div>
</noscript>
<div id="browser-old" hidden style="position:relative;z-index:90;background:#1b2a3a;color:#fff;padding:12px 16px;font:15px/1.4 system-ui,sans-serif;border-bottom:4px solid #f2b705">
    <strong>Browser ini terlalu lama.</strong> Tampilan bisa berantakan. Perbarui ke Chrome, Edge, atau Firefox versi terbaru.
</div>
<script>
    (function () {
        try {
            var ok = window.CSS && CSS.supports('color', 'color-mix(in srgb, red, blue)') && CSS.supports('container-type', 'inline-size');
            if (!ok) document.getElementById('browser-old').hidden = false;
        } catch (e) { document.getElementById('browser-old').hidden = false; }
    })();
</script>
