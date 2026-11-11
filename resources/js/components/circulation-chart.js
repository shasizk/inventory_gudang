import { request } from '../lib/api';

/*
 * Chart.js (±200 kB) hanya dimuat di halaman yang punya grafik (dashboard), tidak ikut membebani halaman lain.
 * Vite memecahnya menjadi file terpisah secara otomatis.
 */
let chartLib;
const loadChart = async () => {
    if (!chartLib) {
        chartLib = (await import('chart.js/auto')).default;
        chartLib.defaults.font.family = "'Barlow', ui-sans-serif, system-ui, sans-serif";
        chartLib.defaults.font.size = 13;
    }
    return chartLib;
};

/** Grafik batang sirkulasi barang masuk vs keluar. Instance Chart disimpan di luar state reaktif Alpine. */
export default ({ endpoint } = {}) => {
    let chart = null;
    const token = (name) => getComputedStyle(document.documentElement).getPropertyValue(name).trim();

    return {
        range: '7d',
        loading: true,
        error: null,
        empty: false,
        totals: { in: 0, out: 0 },
        _seq: 0,

        init() {
            this.load();
            this.$watch('range', () => this.load());
        },

        async load() {
            const seq = ++this._seq; // ganti 7↔30 hari cepat: hanya respons terakhir yang dipakai
            this.loading = true;
            this.error = null;
            try {
                const raw = await request(`${endpoint}?range=${this.range}`);
                if (seq !== this._seq) return;
                const labels = Array.isArray(raw?.labels) ? raw.labels.map(String) : [];
                const series = (list) => labels.map((_, i) => Math.max(0, Number(list?.[i]) || 0)); // panjang selalu = labels
                const data = { labels, in: series(raw?.in), out: series(raw?.out) };
                const sum = (list = []) => list.reduce((a, b) => a + b, 0);
                this.totals = { in: sum(data.in), out: sum(data.out) };
                this.empty = !data.labels?.length || (this.totals.in === 0 && this.totals.out === 0);
                await this.render(data);
            } catch (error) {
                if (seq === this._seq) this.error = error.message;
            } finally {
                if (seq === this._seq) this.loading = false;
            }
        },

        async render(data) {
            const Chart = await loadChart();
            const datasets = [
                { label: 'Masuk', data: data.in, backgroundColor: token('--color-in-600'), borderRadius: 3, maxBarThickness: 16 },
                { label: 'Keluar', data: data.out, backgroundColor: token('--color-out-600'), borderRadius: 3, maxBarThickness: 16 },
            ];
            if (chart) {
                chart.data.labels = data.labels;
                chart.data.datasets.forEach((ds, i) => (ds.data = datasets[i].data));
                chart.update();
                return;
            }
            chart = new Chart(this.$refs.canvas, {
                type: 'bar',
                data: { labels: data.labels, datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: token('--color-ink-900'),
                            padding: 10,
                            callbacks: { label: (ctx) => ` ${ctx.dataset.label}: ${ctx.parsed.y} unit` },
                        },
                    },
                    scales: {
                        x: { grid: { display: false }, ticks: { color: token('--color-ink-500'), maxRotation: 0, autoSkipPadding: 12 } },
                        y: {
                            beginAtZero: true,
                            ticks: { precision: 0, color: token('--color-ink-500') },
                            grid: { color: token('--color-concrete-200') },
                            border: { display: false },
                        },
                    },
                },
            });
        },

        destroy() {
            chart?.destroy();
            chart = null;
        },
    };
};
