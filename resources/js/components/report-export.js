import { isDemo, notifySessionExpired, sleep } from '../lib/util';

/**
 * Konfigurasi & unduh laporan PDF / Excel.
 * Parameter filter diambil dari form filter (id = opsi `form`) + opsi ekspor.
 * Backend mengembalikan file (Content-Disposition: attachment).
 */
export default ({ endpoint, form = 'report-filters' } = {}) => ({
    format: 'pdf',
    orientation: 'landscape',
    paper: 'a4',
    sections: ['stok', 'mutasi', 'absensi', 'akses'],
    signature: true,
    exporting: null,

    params(format) {
        const params = new URLSearchParams(new FormData(document.getElementById(form) ?? undefined));
        params.set('format', format);
        if (format === 'pdf') {
            params.set('orientation', this.orientation);
            params.set('paper', this.paper);
            params.set('signature', this.signature ? 1 : 0);
        }
        this.sections.forEach((s) => params.append('sections[]', s));
        return params;
    },

    async download(format) {
        if (!this.sections.length || this.exporting) return;
        const fd = new FormData(document.getElementById(form) ?? undefined);
        if (fd.get('from') && fd.get('to') && fd.get('from') > fd.get('to')) {
            this.$store.toast.error('Periode tidak valid', 'Tanggal awal tidak boleh setelah tanggal akhir.');
            return;
        }
        this.exporting = format;
        const url = `${endpoint}?${this.params(format)}`;
        try {
            if (isDemo()) {
                await sleep(900);
                this.$store.toast.info('Mode demo: ekspor belum terhubung', `Backend akan menerima GET ${url.replace(location.origin, '')}`, 9000);
                return;
            }
            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), 120_000); // laporan besar boleh lama, tapi tidak selamanya
            let res;
            try {
                res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin', signal: controller.signal });
            } catch {
                throw new Error(controller.signal.aborted ? 'Server terlalu lama menyiapkan file. Coba persempit periode laporan.' : 'Tidak dapat terhubung ke server.');
            } finally {
                clearTimeout(timer);
            }
            if (res.status === 401 || res.status === 419 || (res.redirected && /\/login/.test(res.url))) {
                notifySessionExpired();
                throw new Error('Sesi login berakhir. Masuk lagi lalu unduh ulang.');
            }
            if (!res.ok) throw new Error(res.status === 422 ? 'Periksa kembali filter tanggal.' : `Server menolak permintaan (kode ${res.status}).`);
            // Server kadang mengembalikan halaman HTML (error/login) dengan status 200: jangan disimpan sebagai PDF rusak
            const type = res.headers.get('content-type') ?? '';
            if (/text\/html|application\/json/.test(type)) throw new Error('Server tidak mengirim file laporan. Endpoint ekspor mungkin belum dibuat.');
            const blob = await res.blob();
            if (!blob.size) throw new Error('File laporan kosong. Periksa filter periode.');
            const disposition = res.headers.get('Content-Disposition') ?? '';
            const filename = disposition.match(/filename\*?=(?:UTF-8'')?"?([^";]+)/i)?.[1] ?? `laporan-gudang.${format === 'pdf' ? 'pdf' : 'xlsx'}`;
            const link = Object.assign(document.createElement('a'), { href: URL.createObjectURL(blob), download: decodeURIComponent(filename) });
            link.click();
            URL.revokeObjectURL(link.href);
            this.$store.toast.success('Laporan diunduh', filename);
        } catch (error) {
            this.$store.toast.error('Ekspor gagal', error.message);
        } finally {
            this.exporting = null;
        }
    },
});
