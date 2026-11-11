// JsBarcode hanya dimuat saat ada barcode yang perlu digambar (halaman Serial number)
let jsBarcode;
const loadBarcode = async () => (jsBarcode ??= (await import('jsbarcode')).default);

/**
 * Directive x-barcode="ekspresi" untuk <svg>: menggambar barcode CODE128.
 * Contoh: <svg x-barcode="code"></svg>
 */
export function barcodeDirective(Alpine) {
    Alpine.directive('barcode', (el, { expression }, { effect, evaluateLater }) => {
        const getValue = evaluateLater(expression);
        effect(() =>
            getValue(async (value) => {
                el.innerHTML = '';
                if (!value) return;
                try {
                    const JsBarcode = await loadBarcode();
                    if (el.dataset.rendering === String(value)) return;
                    el.dataset.rendering = String(value);
                    JsBarcode(el, String(value), {
                        format: 'CODE128',
                        height: 54,
                        margin: 6,
                        fontSize: 13,
                        font: 'IBM Plex Mono',
                        lineColor: '#1b2a3a',
                        background: 'transparent',
                    });
                } catch {
                    // Karakter yang tidak didukung CODE128 / gagal memuat library: tampilkan kode sebagai teks, bukan kosong
                    el.innerHTML = '';
                    el.insertAdjacentHTML('beforeend', '<text x="4" y="20" font-family="monospace" font-size="13"></text>');
                    el.lastChild.textContent = String(value);
                } finally {
                    delete el.dataset.rendering;
                }
            }),
        );
    });
}

/**
 * Generator serial number di modal "Generate barcode".
 * Format: {PREFIX}-{KODE KATEGORI}-{YYMMDD}-{4 karakter acak}, mis. SN-RTR-261021-A7K2.
 * Kode hanya diusulkan di browser; backend tetap wajib memvalidasi keunikan sebelum menyimpan.
 */
export const barcodeGenerator = ({ prefix = 'SN', max = 50 } = {}) => ({
    prefix,
    categoryCode: '',
    itemName: '',
    count: 1,
    codes: [],
    max,
    saving: false,

    generate() {
        const n = Math.min(Math.max(1, Number(this.count) || 1), this.max);
        const d = new Date();
        const date = `${String(d.getFullYear()).slice(2)}${String(d.getMonth() + 1).padStart(2, '0')}${String(d.getDate()).padStart(2, '0')}`;
        const alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // tanpa I, O, 0, 1 agar tidak tertukar saat dibaca
        const seen = new Set();
        while (seen.size < n) {
            const rand = Array.from({ length: 4 }, () => alphabet[Math.floor(Math.random() * alphabet.length)]).join('');
            seen.add([this.prefix, this.categoryCode || 'GEN', date, rand].filter(Boolean).join('-').toUpperCase());
        }
        this.codes = [...seen];
    },

    reset() {
        this.codes = [];
        this.count = 1;
    },

    print() {
        window.print();
    },
});
