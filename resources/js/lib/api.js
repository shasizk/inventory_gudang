import { HttpError, isDemo, meta, notifySessionExpired, sessionExpired } from './util';
import { handle as demoHandle } from './demo';

const messageFor = (status) =>
    ({
        0: 'Tidak dapat terhubung ke server. Periksa jaringan lalu coba lagi.',
        401: 'Sesi login berakhir. Masuk lagi untuk melanjutkan.',
        403: 'Akun Anda tidak memiliki izin untuk tindakan ini.',
        404: 'Data tidak ditemukan.',
        408: 'Server tidak merespons. Coba lagi beberapa saat lagi.',
        413: 'Data yang dikirim terlalu besar.',
        419: 'Sesi login berakhir. Masuk lagi untuk melanjutkan.',
        422: 'Data yang dikirim tidak valid.',
        429: 'Terlalu banyak permintaan. Tunggu sebentar lalu coba lagi.',
        500: 'Server mengalami gangguan. Coba lagi beberapa saat lagi.',
        502: 'Server sedang tidak bisa dihubungi. Coba lagi beberapa saat lagi.',
        503: 'Server sedang dalam perawatan. Coba lagi beberapa saat lagi.',
        504: 'Server terlalu lama merespons. Coba lagi beberapa saat lagi.',
    })[status] ?? `Permintaan gagal (kode ${status}).`;

/**
 * Pembungkus fetch untuk endpoint JSON Laravel.
 *
 * - CSRF token & header AJAX otomatis
 * - Batas waktu (bawaan 10 detik): request yang menggantung tidak membuat antrean scan/polling macet
 * - Sesi habis (401, 419, atau diarahkan ke /login) → event global 'session-expired', sekali saja
 * - Respons sukses yang bukan JSON (mis. halaman HTML error) → error yang jelas, bukan data kosong diam-diam
 * - Pesan error dari { message } atau error validasi pertama { errors }
 * - Mode demo (<meta name="ui-demo" content="1">) dijawab simulator lib/demo.js
 */
export async function request(url, { method = 'GET', body, signal, timeout = 10_000 } = {}) {
    if (isDemo()) return demoHandle(method, url, body);
    if (sessionExpired()) throw new HttpError(401, messageFor(401));

    const controller = new AbortController();
    const timer = setTimeout(() => controller.abort('timeout'), timeout);
    signal?.addEventListener('abort', () => controller.abort(signal.reason), { once: true });

    let res;
    try {
        res = await fetch(url, {
            method,
            signal: controller.signal,
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': meta('csrf-token'),
            },
            body: body ? JSON.stringify(body) : undefined,
        });
    } catch (error) {
        if (signal?.aborted) throw error; // dibatalkan pemanggil (mis. ganti rentang grafik)
        throw new HttpError(controller.signal.aborted ? 408 : 0, messageFor(controller.signal.aborted ? 408 : 0));
    } finally {
        clearTimeout(timer);
    }

    if (res.status === 401 || res.status === 419 || (res.redirected && /\/login(\?|$)/.test(new URL(res.url).pathname))) {
        notifySessionExpired();
        throw new HttpError(res.status === 419 ? 419 : 401, messageFor(401));
    }

    const isJson = (res.headers.get('content-type') ?? '').includes('application/json');
    const data = isJson ? await res.json().catch(() => null) : null;

    if (!res.ok) {
        const firstValidation = data?.errors ? Object.values(data.errors)[0]?.[0] : null;
        throw new HttpError(res.status, firstValidation || data?.message || messageFor(res.status), data ?? {});
    }
    if (!isJson || data === null) {
        throw new HttpError(res.status, 'Respons server tidak berformat JSON. Endpoint mungkin belum dibuat atau mengembalikan halaman HTML.');
    }
    return data;
}
