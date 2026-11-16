import { request } from '../lib/api';

export default ({ endpoint, after = 0, updatedAfter, interval = 2000, enabled = true } = {}) => ({
    endpoint,
    after,
    updatedAfter,
    enabled,
    _inflight: false,
    _poll: null,

    init() {
        if (!this.enabled) return;
        this._poll = setInterval(() => {
            if (!document.hidden && !this.$store.session?.expired) this.load();
        }, interval);
    },

    destroy() {
        clearInterval(this._poll);
    },

    async load() {
        if (this._inflight) return;
        this._inflight = true;

        try {
            const url = new URL(this.endpoint, window.location.origin);
            url.searchParams.set('after', this.after);
            url.searchParams.set('updated_after', this.updatedAfter);

            const data = await request(url.toString(), { timeout: 8000, cache: 'no-store' });
            this.after = Math.max(this.after, Number(data?.latest_id) || 0);
            if (data?.updated_after) this.updatedAfter = data.updated_after;
            if (!data?.html) return;

            const template = document.createElement('template');
            template.innerHTML = data.html;
            const tbody = this.$root.querySelector('tbody');
            const emptyRow = tbody?.querySelector('tr.is-empty');

            if (!tbody) return;
            emptyRow?.remove();

            for (const row of [...template.content.querySelectorAll('tr[data-door-log-id]')].reverse()) {
                const current = tbody.querySelector(`[data-door-log-id="${CSS.escape(row.dataset.doorLogId)}"]`);
                if (current) {
                    current.replaceWith(row);
                } else {
                    tbody.prepend(row);
                }
            }

            const rows = [...tbody.querySelectorAll('tr[data-door-log-id]')];
            rows.slice(25).forEach((row) => row.remove());
        } catch (error) {
            console.error('Gagal memperbarui riwayat pintu:', error);
        } finally {
            this._inflight = false;
        }
    },
});
