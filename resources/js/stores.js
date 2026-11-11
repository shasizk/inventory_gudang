import { request } from './lib/api';
import { ago, meta, toDate } from './lib/util';

/** $store.toast — notifikasi global. $store.toast.success('Judul', 'Pesan') / error / warning / info */
export const toastStore = () => ({
    items: [],
    seq: 0,
    push({ type = 'info', title, message = '', timeout = 4500 }) {
        // Pesan identik yang muncul beruntun (mis. scan gagal berulang) cukup diperbarui, tidak ditumpuk
        const same = this.items.find((t) => t.title === title && t.message === message);
        if (same) {
            same.count = (same.count ?? 1) + 1;
            return same.id;
        }
        const id = ++this.seq;
        this.items.push({ id, type, title, message, count: 1 });
        if (this.items.length > 4) this.items.shift();
        if (timeout) setTimeout(() => this.dismiss(id), timeout);
        return id;
    },
    success(title, message, timeout) { return this.push({ type: 'success', title, message, timeout }); },
    error(title, message, timeout = 7000) { return this.push({ type: 'error', title, message, timeout }); },
    warning(title, message, timeout = 6000) { return this.push({ type: 'warning', title, message, timeout }); },
    info(title, message, timeout) { return this.push({ type: 'info', title, message, timeout }); },
    dismiss(id) {
        this.items = this.items.filter((t) => t.id !== id);
    },
});

/** $store.session — peran akun aktif (dari <meta name="user-role">) */
export const sessionStore = () => ({
    role: meta('user-role') || 'guest',
    expired: false,
    init() {
        window.addEventListener('session-expired', () => (this.expired = true));
    },
    get isOwner() {
        return this.role === 'owner';
    },
    get canVerify() {
        return ['owner', 'admin'].includes(this.role);
    },
});

const CACHE_KEY = 'smart-gudang:door-status';
const CACHE_TTL = 60_000;

/**
 * $store.door — status Smart Lock, dipakai bersama oleh topbar, dashboard, dan notifikasi verifikasi.
 *
 * Perbaikan v1.1:
 * - Status terakhir di-cache di sessionStorage → indikator topbar langsung tampil saat pindah halaman (tanpa layout shift).
 * - Hitung mundur verifikasi 2 langkah tidak "melompat" setiap polling: expiresAt hanya diperbarui bila selisihnya > 2 detik.
 * - Transisi pending → selesai dicatat sebagai event 'opened' atau 'expired' agar UI bisa memberi umpan balik.
 */
export const doorStore = () => ({
    doors: [],
    events: {}, // { [doorId]: { type: 'opened'|'expired', technician, at } }
    loading: true,
    error: null,
    updatedAt: null,
    now: Date.now(),

    failures: 0,
    _inflight: false,
    _nextAt: 0,

    init() {
        this.hydrate();
        const url = meta('door-status-url');
        if (!url) {
            this.loading = false;
            return;
        }
        const load = async (force = false) => {
            // Lewati bila: request sebelumnya belum selesai (server lambat), tab tidak dilihat, sesi habis,
            // atau sedang jeda setelah gagal beruntun (5 → 10 → 20 → maks 60 detik)
            if (this._inflight || window.Alpine?.store('session')?.expired) return;
            if (!force && ((document.hidden && this.updatedAt) || Date.now() < this._nextAt)) return;
            this._inflight = true;
            try {
                this.apply(await request(url, { timeout: 8000 }));
                this.error = null;
                this.failures = 0;
                this._nextAt = 0;
            } catch (error) {
                this.error = error.message;
                this.failures++;
                this._nextAt = Date.now() + Math.min(60_000, 5000 * 2 ** (this.failures - 1));
            } finally {
                this._inflight = false;
                this.loading = false;
            }
        };
        load(true);
        setInterval(load, 5000);
        setInterval(() => (this.now = Date.now()), 1000);
        document.addEventListener('visibilitychange', () => !document.hidden && load(true));
    },

    /** Data dari backend dirapikan agar tampilan tidak error walau ada field yang kosong/berbeda tipe */
    normalize(door, index) {
        const pending = door?.pending && typeof door.pending === 'object'
            ? {
                  ...door.pending,
                  technician: door.pending.technician || 'Teknisi',
                  role: door.pending.role || 'Teknisi',
                  expires_in: Math.max(0, Number(door.pending.expires_in) || 0),
                  timeout: Math.max(1, Number(door.pending.timeout) || 30),
              }
            : null;
        return {
            ...door,
            id: String(door?.id ?? `pintu-${index}`),
            name: door?.name || `Pintu ${index + 1}`,
            short: door?.short || door?.name || `Pintu ${index + 1}`,
            online: door?.online === true || door?.online === 1 || door?.online === '1',
            locked: !(door?.locked === false || door?.locked === 0 || door?.locked === '0'),
            last_seen: toDate(door?.last_seen) ? door.last_seen : null,
            last_event: door?.last_event || 'Belum ada aktivitas tercatat.',
            pending,
        };
    },

    hydrate() {
        try {
            const cached = JSON.parse(sessionStorage.getItem(CACHE_KEY) ?? 'null');
            if (cached && Date.now() - cached.savedAt < CACHE_TTL) {
                this.doors = cached.doors;
                this.updatedAt = cached.savedAt;
                this.loading = false;
            }
        } catch {
            /* sessionStorage tidak tersedia: abaikan */
        }
    },

    apply(data) {
        const t = Date.now();
        const previous = Object.fromEntries(this.doors.map((d) => [d.id, d]));

        const list = Array.isArray(data?.doors) ? data.doors : [];
        this.doors = list.map((raw, index) => this.normalize(raw, index)).map((door) => {
            const before = previous[door.id];
            let expiresAt = null;
            if (door.pending) {
                const fresh = t + door.pending.expires_in * 1000;
                const keep = before?.pending && before.pending.technician === door.pending.technician && before.expiresAt;
                expiresAt = keep && Math.abs(before.expiresAt - fresh) <= 2000 ? before.expiresAt : fresh;
            }
            if (before?.pending && !door.pending) {
                this.events[door.id] = {
                    type: door.locked ? 'expired' : 'opened',
                    technician: before.pending.technician,
                    at: t,
                };
                setTimeout(() => delete this.events[door.id], 6000);
            }
            return { ...door, expiresAt };
        });
        this.updatedAt = t;
        try {
            sessionStorage.setItem(CACHE_KEY, JSON.stringify({ doors: this.doors, savedAt: t }));
        } catch {
            /* kuota penuh / mode privat */
        }
    },

    remaining(door) {
        return door.expiresAt ? Math.max(0, Math.ceil((door.expiresAt - this.now) / 1000)) : 0;
    },
    /** 0–1, untuk cincin hitung mundur. Batas waktu standar 30 detik. */
    progress(door, total = 30) {
        return Math.min(1, this.remaining(door) / (Number(total) || 30));
    },
    phase(door) {
        if (!door.online) return 'offline';
        if (door.pending) return this.remaining(door) > 0 ? 'waiting' : 'timeout';
        return door.locked ? 'locked' : 'open';
    },
    get pendingDoors() {
        return this.doors.filter((d) => d.pending);
    },
    seen(door) {
        this.now;
        return door.last_seen ? ago(door.last_seen) : 'belum pernah';
    },
    stateLabel(door) {
        return {
            offline: 'Offline',
            waiting: 'Menunggu verifikasi',
            timeout: 'Waktu habis',
            locked: 'Terkunci',
            open: 'Terbuka',
        }[this.phase(door)];
    },
    get updatedLabel() {
        this.now;
        return this.updatedAt ? ago(this.updatedAt) : '';
    },
});
