import { request } from '../lib/api';
import { beep } from '../lib/util';

/**
 * Menunggu kartu RFID ditempel ke reader ESP32.
 * ESP32 mengirim UID ke server; halaman ini melakukan polling ke endpoint
 * sampai ada UID baru yang dibaca SETELAH tombol ditekan (?since=timestamp).
 * UID yang tertangkap dikirim ke form induk lewat event "rfid-captured".
 * Reader USB (mode keyboard) juga bisa langsung mengetik ke kolom UID.
 */
export default ({ endpoint, timeout = 30 } = {}) => ({
    waiting: false,
    remaining: 0,
    _poll: null,
    _tick: null,

    start() {
        if (this.waiting) return;
        this.waiting = true;
        this.remaining = timeout;
        const since = Date.now();

        this._tick = setInterval(() => {
            this.remaining--;
            if (this.remaining <= 0) this.stop('timeout');
        }, 1000);

        const poll = async () => {
            if (!this.waiting) return;
            try {
                const data = await request(`${endpoint}?since=${since}`);
                const uid = typeof data?.uid === 'string' || typeof data?.uid === 'number' ? String(data.uid).replace(/[^0-9a-f]/gi, '') : '';
                if (uid && this.waiting) {
                    this.$dispatch('rfid-captured', uid.toUpperCase());
                    this.stop('found', data.reader);
                    return;
                }
            } catch (error) {
                // Gangguan sesaat (timeout/jaringan) dicoba lagi; error permanen menghentikan penantian
                if (![0, 408, 502, 503, 504].includes(error.status)) {
                    this.stop('error', error.message);
                    return;
                }
            }
            this._poll = setTimeout(poll, 1500);
        };
        poll();
    },

    stop(reason = 'cancel', info = '') {
        clearInterval(this._tick);
        clearTimeout(this._poll);
        this.waiting = false;
        if (reason === 'found') {
            beep(true);
            this.$store.toast.success('Kartu terbaca', info ? `UID diambil dari ${info}.` : 'UID sudah diisi otomatis.');
        } else if (reason === 'timeout') {
            beep(false);
            this.$store.toast.warning('Tidak ada kartu terbaca', 'Tempelkan kartu ke reader dalam 30 detik, lalu tekan Tunggu tap kartu lagi.');
        } else if (reason === 'error') {
            this.$store.toast.error('Reader tidak merespons', info);
        }
    },

    destroy() {
        this.stop();
    },
});
