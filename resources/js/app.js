import Alpine from 'alpinejs';
import focus from '@alpinejs/focus';

import { doorStore, sessionStore, toastStore } from './stores';
import scanStation from './components/scan-station';
import rfidCapture from './components/rfid-capture';
import crudForm from './components/crud-form';
import circulationChart from './components/circulation-chart';
import liveFeed from './components/live-feed';
import reportExport from './components/report-export';
import { barcodeDirective, barcodeGenerator } from './components/barcode';
import { isDemo } from './lib/util';

window.Alpine = Alpine;

Alpine.plugin(focus);
Alpine.plugin(barcodeDirective);

Alpine.store('toast', toastStore());
Alpine.store('session', sessionStore());
Alpine.store('door', doorStore());

Alpine.data('scanStation', scanStation);
Alpine.data('rfidCapture', rfidCapture);
Alpine.data('crudForm', crudForm);
Alpine.data('circulationChart', circulationChart);
Alpine.data('liveFeed', liveFeed);
Alpine.data('reportExport', reportExport);
Alpine.data('barcodeGenerator', barcodeGenerator);

/*
 * Halaman dipulihkan dari back/forward cache (tombol Kembali browser) membawa state lama:
 * tombol masih "Menyimpan", overlay loading tabel masih tampil. Muat ulang agar state bersih.
 */
window.addEventListener('pageshow', (event) => event.persisted && window.location.reload());

/* Mode demo/lokal: error JavaScript ditampilkan sebagai toast agar langsung terlihat saat QA (produksi: diam, cukup di console) */
if (isDemo()) {
    const report = (message) => Alpine.store('toast')?.error('Error tampilan (khusus mode demo)', String(message).slice(0, 180), 10_000);
    window.addEventListener('error', (e) => report(e.message));
    window.addEventListener('unhandledrejection', (e) => report(e.reason?.message ?? e.reason));
}

Alpine.start();
