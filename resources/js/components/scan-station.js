import { request } from '../lib/api';
import { demoCodes } from '../lib/demo';
import { HttpError, beep, clock, isDemo } from '../lib/util';

/**
 * Stasiun scan barcode — v1.1
 *
 * Scanner USB/Bluetooth = "keyboard" yang mengetik sangat cepat lalu mengirim terminator (Enter, kadang Tab).
 *
 * Perbaikan dibanding v1.0:
 * 1. ANTREAN: scan yang datang saat request sebelumnya masih berjalan tidak lagi hilang, tapi diantre (FIFO).
 *    Kolom input tidak dinonaktifkan selama loading, jadi ketikan scanner tidak "jatuh" ke body.
 * 2. DEBOUNCE DUPLIKAT: kode yang sama dalam `duplicateWindow` ms (pantulan / scan dobel tak sengaja) diabaikan
 *    dengan umpan balik jelas. Scan barang yang sama secara sengaja tetap bisa setelah jeda itu.
 * 3. TERMINATOR: Enter, NumpadEnter, dan Tab (opsional) diterima. Karakter kontrol & prefix AIM (]C1, ]E0) dibuang.
 * 4. JUMLAH: dibatasi bilangan bulat 1–`maxQty`; nilai kosong/negatif/huruf tidak pernah terkirim.
 * 5. FOKUS: kembali ke kolom scan setelah submit, setelah modal ditutup, saat tab browser aktif lagi,
 *    dan saat operator mengklik area kosong stasiun.
 *
 * mode 'in'  (Owner)     : GET  endpoint?code=...           -> event 'scan-result' untuk membuka form Barang Masuk
 * mode 'out' (non-Owner) : POST endpoint {code, qty, source} -> langsung mencatat Barang Keluar
 */
export default ({
    mode = 'out',
    endpoint,
    minLength = 4,
    maxLength = 64,
    maxQty = 999,
    duplicateWindow = 1500,
    keyGap = 50, // jeda maksimum antar karakter (ms) agar dianggap berasal dari scanner
    acceptTab = true,
} = {}) => ({
    mode,
    code: '',
    qty: 1,
    state: 'idle', // idle | reading | loading | success | warning | error
    message: '',
    recent: [],
    queue: [],
    demo: isDemo(),
    demoCodes,
    busy: false,
    _gaps: [],
    _last: 0,
    _buffer: '',
    _bufferAt: 0,
    _lastCode: '',
    _lastCodeAt: 0,

    init() {
        this.message = this.idleMessage();
        this._onKey = (e) => this.captureGlobal(e);
        this._refocus = () => setTimeout(() => this.focus(), 60);
        this._onVisible = () => !document.hidden && this._refocus();
        window.addEventListener('keydown', this._onKey, true);
        window.addEventListener('close-modal', this._refocus);
        window.addEventListener('focus', this._refocus);
        document.addEventListener('visibilitychange', this._onVisible);
        this.$watch('qty', (v) => (this.qty = this.clampQty(v, true)));
        this.$nextTick(() => this.focus());
    },

    destroy() {
        window.removeEventListener('keydown', this._onKey, true);
        window.removeEventListener('close-modal', this._refocus);
        window.removeEventListener('focus', this._refocus);
        document.removeEventListener('visibilitychange', this._onVisible);
    },

    idleMessage() {
        return this.mode === 'in'
            ? 'Siap. Scan barcode barang yang datang, form barang masuk akan terbuka otomatis.'
            : 'Siap. Scan barcode barang yang akan dibawa keluar gudang.';
    },

    focus() {
        const active = document.activeElement;
        const typingElsewhere = active && active !== this.$refs.input && active.matches?.('input, textarea, select, [contenteditable="true"]');
        if (document.querySelector('[data-modal-open]') || typingElsewhere) return;
        this.$refs.input?.focus({ preventScroll: true });
    },

    isTerminator(e) {
        return e.key === 'Enter' || (acceptTab && e.key === 'Tab' && !e.shiftKey);
    },

    /** Dipanggil dari kolom scan (keydown) */
    onInputKey(e) {
        if (this.isTerminator(e)) {
            // Tab dari scanner tidak boleh memindahkan fokus; Tab manual (ketikan lambat) tetap berfungsi normal
            if (e.key === 'Tab' && this.source() !== 'scanner') return;
            e.preventDefault();
            this.enqueue(this.code, this.source());
            this.code = '';
            return;
        }
        if (e.key.length !== 1) return;
        const now = performance.now();
        if (this._last) this._gaps.push(now - this._last);
        if (this._gaps.length > 24) this._gaps.shift();
        this._last = now;
    },

    source() {
        const gaps = this._gaps;
        const avg = gaps.reduce((a, b) => a + b, 0) / (gaps.length || 1);
        return gaps.length >= 3 && avg < 40 ? 'scanner' : 'manual';
    },

    /** Menangkap scanner saat fokus tidak di kolom mana pun (operator tidak perlu klik kolom dulu) */
    captureGlobal(e) {
        const target = e.target;
        if (target === this.$refs.input) return;
        if (target.closest?.('input, textarea, select, [contenteditable="true"]')) return;
        if (document.querySelector('[data-modal-open]')) return;
        if (e.ctrlKey || e.metaKey || e.altKey) return;

        const now = performance.now();
        if (this.isTerminator(e)) {
            if (this._buffer.length >= minLength) {
                e.preventDefault();
                this.enqueue(this._buffer, 'scanner');
            }
            this._buffer = '';
            return;
        }
        if (e.key.length !== 1) return;
        if (now - this._bufferAt > keyGap) this._buffer = '';
        this._buffer += e.key;
        this._bufferAt = now;
        if (this._buffer.length > 2 && !this.busy) this.state = 'reading';
    },

    useDemoCode(code) {
        this.enqueue(code, 'manual');
    },

    sanitize(raw) {
        return String(raw ?? '')
            .replace(/[\u0000-\u001F\u007F]/g, '') // karakter kontrol (CR, LF, GS dari scanner GS1)
            .replace(/^\][A-Za-z]\d/, '') // prefix AIM symbology identifier, mis. ]C1
            .trim()
            .toUpperCase();
    },

    clampQty(value, allowEmpty = false) {
        if (allowEmpty && (value === '' || value === null)) return value;
        const n = Math.floor(Number(value));
        if (!Number.isFinite(n) || n < 1) return 1;
        return Math.min(n, maxQty);
    },

    enqueue(raw, source = 'manual') {
        const code = this.sanitize(raw);
        this._gaps = [];
        this._last = 0;
        if (!code) return;

        if (code.length < minLength) return this.fail(code, `Kode terlalu pendek (${code.length} karakter). Pastikan seluruh barcode terbaca, lalu scan ulang.`);
        if (code.length > maxLength) return this.fail(code.slice(0, 16) + '…', 'Kode terlalu panjang. Kemungkinan dua barcode terbaca sekaligus, scan ulang satu per satu.');

        const now = Date.now();
        if (code === this._lastCode && now - this._lastCodeAt < duplicateWindow) {
            this.state = 'warning';
            this.message = `${code} terbaca dua kali berturut-turut. Scan kedua diabaikan agar tidak tercatat ganda.`;
            beep(false);
            return;
        }
        this._lastCode = code;
        this._lastCodeAt = now;

        this.queue.push({ code, source, qty: this.mode === 'out' ? this.clampQty(this.qty) : 1 });
        this.qty = 1;
        this.process();
    },

    async process() {
        if (this.busy) return;
        this.busy = true;
        while (this.queue.length) {
            if (this.$store.session?.expired) {
                // Sesi habis: antrean tidak dikirim (akan ditolak server); operator diarahkan masuk lagi
                const lost = this.queue.length;
                this.queue = [];
                this.fail('', `Sesi login berakhir. ${lost} scan belum tercatat, silakan masuk lagi lalu scan ulang.`);
                break;
            }
            const job = this.queue.shift();
            this.state = 'loading';
            this.message = this.queue.length ? `Memeriksa ${job.code} (${this.queue.length} scan lagi dalam antrean)` : `Memeriksa ${job.code}`;
            try {
                const data =
                    this.mode === 'in'
                        ? await request(`${endpoint}?code=${encodeURIComponent(job.code)}`)
                        : await request(endpoint, { method: 'POST', body: { code: job.code, qty: job.qty, source: job.source } });
                this.assertShape(data);
                this.mode === 'in' ? this.handleIn(job.code, data) : this.handleOut(job, data);
                // Mode masuk membuka form; sisa antrean ditahan sampai form ditutup agar data tidak tertukar
                if (this.mode === 'in' && this.queue.length) {
                    this.message += ` ${this.queue.length} scan lain menunggu, akan diproses setelah form ditutup.`;
                    // Batas aman 3 menit: antrean tidak macet selamanya bila modal gagal terbuka
                    await new Promise((resolve) => {
                        const done = () => { clearTimeout(t); window.removeEventListener('close-modal', done); resolve(); };
                        const t = setTimeout(done, 180_000);
                        window.addEventListener('close-modal', done);
                    });
                }
            } catch (error) {
                this.fail(job.code, error.message);
            }
        }
        this.busy = false;
        this.$nextTick(() => this.focus());
    },

    /** Respons yang bentuknya tidak sesuai kontrak ditolak dengan pesan jelas, bukan "Cannot read properties of undefined" */
    assertShape(data) {
        const ok = this.mode === 'in'
            ? typeof data?.found === 'boolean' && (!data.found || (data.item && data.item.name))
            : data?.item && data.item.name && data.item.stock_after !== undefined;
        if (!ok) throw new HttpError(500, 'Respons server tidak sesuai format yang diharapkan. Laporkan ke tim backend (lihat kontrak-payload.md).');
    },

    handleIn(code, data) {
        const prefill = data.found
            ? { name: data.item.name, serial: code, category_id: data.item.category_id ?? '', supplier: data.item.supplier ?? '', qty: 1 }
            : { name: '', serial: code, category_id: '', supplier: '', qty: 1 };
        this.state = 'success';
        this.message = data.found
            ? `${data.item.name} dikenali. Lengkapi jumlah dan supplier di form.`
            : `${code} belum terdaftar. Isi nama dan kategori barang di form.`;
        this.log(code, data.found ? data.item.name : 'Barang baru', 'success');
        beep(true);
        window.dispatchEvent(new CustomEvent('scan-result', { detail: { mode: 'in', code, found: data.found, prefill } }));
    },

    handleOut(job, data) {
        const item = { ...data.item, stock_after: Number(data.item.stock_after) || 0, low_stock: Boolean(data.item.low_stock) };
        this.state = item.low_stock ? 'warning' : 'success';
        this.message = `${item.name} keluar ${job.qty} unit. Sisa stok ${item.stock_after}.` + (item.low_stock ? ' Stok sudah di bawah batas minimum.' : '');
        this.log(job.code, item.name, this.state, `−${job.qty}, sisa ${item.stock_after}`);
        beep(true);
        this.$store.toast[item.low_stock ? 'warning' : 'success'](item.low_stock ? 'Barang keluar, stok menipis' : 'Barang keluar tercatat', this.message);
        window.dispatchEvent(new CustomEvent('scan-result', { detail: { mode: 'out', code: job.code, data } }));
    },

    fail(code, message) {
        this.state = 'error';
        this.message = message;
        this.log(code || '(kosong)', 'Scan gagal', 'error', '');
        beep(false);
        this.$store.toast.error('Scan gagal', message);
    },

    log(code, label, status, note = '') {
        this.recent.unshift({ id: `${Date.now()}-${Math.random().toString(36).slice(2, 6)}`, code, label, status, note, time: clock(new Date()) });
        this.recent = this.recent.slice(0, 6);
    },

    get statusClass() {
        return {
            idle: 'bg-concrete-100 text-ink-700',
            reading: 'bg-in-50 text-in-700',
            loading: 'bg-in-50 text-in-700',
            success: 'bg-ok-50 text-ok-700',
            warning: 'bg-warn-50 text-warn-700',
            error: 'bg-bad-50 text-bad-700',
        }[this.state];
    },
});
