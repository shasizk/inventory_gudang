/**
 * Simulator backend untuk mode demo / pratinjau sidang.
 * Bentuk respons di sini = kontrak payload di docs/kontrak-payload.md.
 * Tidak dipakai sama sekali saat <meta name="ui-demo" content="0">.
 */
import { HttpError, meta, sleep } from './util';

export const catalog = {
    'SN-RTR-261021-A7K2': { id: 1, name: 'Router MikroTik hEX RB750Gr3', category: 'Perangkat jaringan', category_id: 1, supplier: 'PT Citra Jaringan', stock: 12, min_stock: 5 },
    '8991002105539': { id: 2, name: 'Kabel UTP Cat6 roll 305 m', category: 'Kabel & konektor', category_id: 2, supplier: 'CV Kabel Nusantara', stock: 4, min_stock: 3 },
    'SN-ONT-261002-Q9M1': { id: 3, name: 'ONT ZTE F670L', category: 'Perangkat FTTH', category_id: 3, supplier: 'PT Optik Prima', stock: 0, min_stock: 4 },
    'SN-SFP-260930-L3P8': { id: 4, name: 'Modul SFP 1.25G 20 km', category: 'Perangkat jaringan', category_id: 1, supplier: 'PT Citra Jaringan', stock: 27, min_stock: 6 },
};
export const demoCodes = [...Object.keys(catalog), 'SN-XXX-000000-0000'];

const people = [
    ['Rizky Pratama', 'Teknisi'],
    ['Fajar Nugroho', 'NOC'],
    ['Dewi Lestari', 'Owner'],
    ['Andi Saputra', 'Admin'],
];
const pick = (list) => list[Math.floor(Math.random() * list.length)];
const iso = (offsetMs = 0) => new Date(Date.now() - offsetMs).toISOString();

/* ---------- Status pintu ----------
 * Jadwal berbasis jam dinding (siklus 60 detik) agar hitung mundur di browser selalu konsisten:
 *  detik  0–5   : Ruang ICT offline (hanya di siklus ganjil)
 *  detik 15–45  : Teknisi sudah tap, menunggu Admin/Owner (30 detik)
 *                 siklus genap  → Admin tap di detik 32, pintu terbuka 32–40
 *                 siklus ganjil → tidak ada yang tap, waktu habis di detik 45
 */
function doorStatus() {
    const nowSec = Date.now() / 1000;
    const cycle = Math.floor(nowSec / 60);
    const t = nowSec % 60;
    const adminTaps = cycle % 2 === 0;
    const offline = cycle % 2 === 1 && t < 5;
    const pending = t >= 15 && t < (adminTaps ? 32 : 45);
    const opened = adminTaps && t >= 32 && t < 40;
    return {
        server_time: iso(),
        doors: [
            {
                id: 'gudang',
                name: 'Pintu gudang',
                short: 'Gudang',
                online: true,
                locked: true,
                last_seen: iso(2000),
                last_event: 'Dewi Lestari (Owner) masuk dengan kartu RFID pukul 09.12',
                pending: null,
            },
            {
                id: 'ict',
                name: 'Ruang ICT',
                short: 'ICT',
                online: !offline,
                locked: !opened,
                last_seen: offline ? iso(48000) : iso(1500),
                last_event: opened
                    ? 'Pintu dibuka: Rizky Pratama (Teknisi) didampingi Andi Saputra (Admin)'
                    : 'Fajar Nugroho (NOC) keluar pukul 08.47',
                pending: pending
                    ? { step: 2, technician: 'Rizky Pratama', role: 'Teknisi', expires_in: Math.max(0, Math.round(45 - t)), timeout: 30 }
                    : null,
            },
        ],
    };
}

/* ---------- Tap kartu RFID untuk registrasi ---------- */
function rfidPending(url) {
    const since = Number(url.searchParams.get('since')) || Date.now();
    if (Date.now() - since < 4000) return { uid: null };
    const uid = Array.from({ length: 4 }, () => Math.floor(Math.random() * 256).toString(16).padStart(2, '0'))
        .join('')
        .toUpperCase();
    return { uid, reader: 'Reader pintu gudang', read_at: iso() };
}

/* ---------- Aktivitas terbaru ---------- */
let activitySeq = 100;
const seedActivity = () => [
    { type: 'door_in', actor: 'Dewi Lestari', role: 'Owner', detail: 'Masuk dengan kartu RFID', location: 'gudang', at: iso(60_000) },
    { type: 'item_out', actor: 'Rizky Pratama', role: 'Teknisi', detail: 'Router MikroTik hEX RB750Gr3 keluar 1 unit', location: 'gudang', at: iso(240_000) },
    { type: 'denied', actor: 'Kartu tidak dikenal', role: null, detail: 'UID 4F A1 09 C2 ditolak', location: 'ict', at: iso(600_000) },
    { type: 'item_in', actor: 'Dewi Lestari', role: 'Owner', detail: 'Modul SFP 1.25G 20 km masuk 10 unit', location: 'gudang', at: iso(1_500_000) },
    { type: 'door_in', actor: 'Fajar Nugroho', role: 'NOC', detail: 'Masuk dengan verifikasi 2 langkah', location: 'ict', at: iso(2_700_000) },
].map((item, i) => ({ id: activitySeq - i, ...item }));

function activity(url) {
    const after = Number(url.searchParams.get('after')) || 0;
    if (!after) return { items: seedActivity() };
    if (Math.random() > 0.35) return { items: [] };
    const [actor, role] = pick(people);
    const templates = [
        { type: 'door_in', detail: 'Masuk dengan kartu RFID', location: pick(['gudang', 'ict']) },
        { type: 'door_out', detail: 'Keluar ruangan', location: pick(['gudang', 'ict']) },
        { type: 'item_out', detail: `${pick(Object.values(catalog)).name} keluar 1 unit`, location: 'gudang' },
    ];
    return { items: [{ id: ++activitySeq, actor, role, at: iso(), ...pick(templates) }] };
}

/* ---------- Grafik sirkulasi ---------- */
function circulation(url) {
    const days = url.searchParams.get('range') === '30d' ? 30 : 7;
    const labels = [];
    const incoming = [];
    const outgoing = [];
    for (let i = days - 1; i >= 0; i--) {
        const d = new Date(Date.now() - i * 86_400_000);
        labels.push(d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' }));
        incoming.push(d.getDay() === 0 ? 0 : Math.round(4 + Math.random() * 18));
        outgoing.push(d.getDay() === 0 ? 0 : Math.round(3 + Math.random() * 14));
    }
    return { labels, in: incoming, out: outgoing };
}

/* ---------- Scan barang ---------- */
function lookup(code) {
    const item = catalog[code?.trim().toUpperCase()];
    if (item) return { found: true, item: { ...item, serial: code } };
    return { found: false, item: null };
}

function scanOut(body = {}) {
    const role = meta('user-role');
    if (role === 'owner') throw new HttpError(403, 'Akun Owner mencatat barang masuk. Scan barang keluar dilakukan oleh akun Admin, Teknisi, atau NOC.');
    const code = String(body.code || '').trim().toUpperCase();
    const item = catalog[code];
    const qty = Math.max(1, Number(body.qty) || 1);
    if (!item) throw new HttpError(404, `Kode ${code} tidak terdaftar. Periksa label atau daftarkan serial number terlebih dahulu.`);
    if (item.stock < qty) throw new HttpError(422, `Stok ${item.name} tersisa ${item.stock}. Jumlah keluar melebihi stok.`);
    item.stock -= qty;
    return {
        status: 'ok',
        movement: { id: Date.now(), type: 'out', qty, at: iso() },
        item: { id: item.id, name: item.name, serial: code, category: item.category, stock_after: item.stock, low_stock: item.stock <= item.min_stock },
        message: `${item.name} keluar ${qty} unit.`,
    };
}

export async function handle(method, rawUrl, body) {
    const url = new URL(rawUrl, window.location.origin);
    const path = url.pathname;
    await sleep(path.includes('/door/') ? 120 : 380 + Math.random() * 320);

    if (path.endsWith('/door/status')) return doorStatus();
    if (path.endsWith('/rfid/pending')) return rfidPending(url);
    if (path.endsWith('/activity/latest')) return activity(url);
    if (path.endsWith('/stats/circulation')) return circulation(url);
    if (path.endsWith('/barang/lookup')) return lookup(url.searchParams.get('code'));
    if (path.endsWith('/barang/keluar/scan') && method === 'POST') return scanOut(body);

    throw new HttpError(404, `Simulator demo belum mengenal endpoint ${path}.`);
}
