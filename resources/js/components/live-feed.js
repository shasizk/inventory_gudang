import { request } from '../lib/api';
import { ago } from '../lib/util';

const TYPES = {
    door_in: { label: 'IN', badge: 'badge-in', icon: 'in' },
    door_out: { label: 'OUT', badge: 'badge-out', icon: 'out' },
    item_in: { label: 'IN', badge: 'badge-in', icon: 'box' },
    item_out: { label: 'OUT', badge: 'badge-out', icon: 'box' },
    denied: { label: 'Ditolak', badge: 'badge-bad', icon: 'x' },
};

/** Log aktivitas terbaru (pintu + barang), polling tiap beberapa detik. */
export default ({ endpoint, limit = 8, interval = 5000 } = {}) => ({
    items: [],
    loading: true,
    error: null,
    paused: false,
    now: Date.now(),

    init() {
        this.load(true);
        this._poll = setInterval(() => !this.paused && !document.hidden && !this.$store.session?.expired && this.load(), interval);
        this._tick = setInterval(() => (this.now = Date.now()), 15000);
    },

    destroy() {
        clearInterval(this._poll);
        clearInterval(this._tick);
    },

    async load(first = false) {
        if (this._inflight) return; // server lambat: jangan menumpuk request
        this._inflight = true;
        try {
            const after = first ? 0 : this.items[0]?.id ?? 0;
            const data = await request(`${endpoint}?after=${encodeURIComponent(after)}&limit=${limit}`, { timeout: 8000 });
            const known = new Set(this.items.map((i) => String(i.id)));
            const incoming = (Array.isArray(data?.items) ? data.items : [])
                .filter((item) => item && item.id !== undefined && !known.has(String(item.id))) // tidak ada baris dobel
                .map((item) => ({ ...item, actor: item.actor || 'Tidak diketahui', detail: item.detail || '', fresh: !first }));
            if (incoming.length) this.items = [...incoming, ...this.items].slice(0, limit);
            this.error = null;
        } catch (error) {
            this.error = error.message;
        } finally {
            this._inflight = false;
            this.loading = false;
        }
    },

    meta(item) {
        return TYPES[item.type] ?? { label: item.type, badge: 'badge-neutral', icon: 'box' };
    },
    when(item) {
        this.now;
        return ago(item.at);
    },
});
