export const meta = (name) => document.querySelector(`meta[name="${name}"]`)?.content ?? '';

export const isDemo = () => meta('ui-demo') === '1';

export const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

export class HttpError extends Error {
    constructor(status, message, data = {}) {
        super(message);
        this.status = status;
        this.data = data;
    }
}

/** Tanggal valid atau null. Tidak pernah menghasilkan "Invalid Date". */
export function toDate(value) {
    if (value === null || value === undefined || value === '') return null;
    const d = value instanceof Date ? value : new Date(typeof value === 'string' && /^\d{4}-\d{2}-\d{2} /.test(value) ? value.replace(' ', 'T') : value);
    return Number.isNaN(d.getTime()) ? null : d;
}

/** "baru saja", "3 menit lalu", dst. Data waktu yang rusak menghasilkan "-", bukan "NaN detik lalu". */
export function ago(value) {
    const date = toDate(value);
    if (!date) return '-';
    const s = Math.max(0, Math.round((Date.now() - date.getTime()) / 1000));
    if (s < 10) return 'baru saja';
    if (s < 60) return `${s} detik lalu`;
    const m = Math.floor(s / 60);
    if (m < 60) return `${m} menit lalu`;
    const h = Math.floor(m / 60);
    if (h < 24) return `${h} jam lalu`;
    return date.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
}

export const clock = (value) => {
    const date = toDate(value);
    return date ? date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' }) : '-';
};

/** Event global sekali kirim saat sesi login berakhir (401/419/diarahkan ke halaman login). */
let sessionExpiredSent = false;
export function notifySessionExpired() {
    if (sessionExpiredSent) return;
    sessionExpiredSent = true;
    window.dispatchEvent(new CustomEvent('session-expired'));
}
export const sessionExpired = () => sessionExpiredSent;

/** Bunyi umpan balik scan: nada tinggi pendek = sukses, nada rendah = gagal. */
let audio;
export function beep(ok = true) {
    try {
        audio ??= new (window.AudioContext || window.webkitAudioContext)();
        const osc = audio.createOscillator();
        const gain = audio.createGain();
        const duration = ok ? 0.12 : 0.32;
        osc.type = ok ? 'sine' : 'square';
        osc.frequency.value = ok ? 1175 : 196;
        gain.gain.setValueAtTime(0.07, audio.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.0001, audio.currentTime + duration);
        osc.connect(gain).connect(audio.destination);
        osc.start();
        osc.stop(audio.currentTime + duration);
    } catch {
        /* browser menolak audio sebelum ada interaksi; abaikan */
    }
}
