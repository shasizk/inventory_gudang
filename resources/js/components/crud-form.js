/**
 * Form CRUD di dalam modal, memakai form POST standar Laravel (bukan AJAX)
 * agar validasi, redirect, dan flash message tetap ditangani controller.
 *
 * Membuka form:
 *   $dispatch('open-modal', { name: 'kategori-form', mode: 'create' })
 *   $dispatch('open-modal', { name: 'kategori-form', mode: 'edit', data: { id: 3, name: '...' } })
 *
 * Saat validasi gagal, Laravel redirect back dengan old input + $errors:
 * form otomatis terisi ulang dan modal dibuka kembali (opsi old & reopen).
 */
/**
 * Angka diubah jadi teks agar <select x-model> mengenali pilihannya:
 * Alpine membandingkan nilai option secara ketat, jadi category_id: 1 (angka) ≠ "1" (teks) → dropdown tampil kosong.
 * Boolean (checkbox) dan null dibiarkan apa adanya.
 */
const normalize = (data = {}) =>
    Object.fromEntries(Object.entries(data ?? {}).map(([k, v]) => [k, typeof v === 'number' ? String(v) : v ?? '']));

export default ({ name, storeUrl, updateUrl, defaults = {}, old = null, reopen = false } = {}) => ({
    form: normalize(defaults),
    editing: null,
    saving: false,

    init() {
        if (old && reopen) {
            this.editing = old._editing || null;
            const { _token, _method, _editing, ...fields } = old;
            this.form = { ...normalize(defaults), ...normalize(fields) };
            this.$nextTick(() => {
                this.$dispatch('open-modal', name);
                this.focusFirst();
            });
        }
    },

    /** Cegah kirim ganda (Enter beruntun / klik dobel). Pakai: x-on:submit="guard($event)" */
    guard(event) {
        if (this.saving) {
            event.preventDefault();
            return;
        }
        this.saving = true;
    },

    get action() {
        return this.editing ? updateUrl.replace('__ID__', this.editing) : storeUrl;
    },

    open(detail) {
        const target = typeof detail === 'string' ? detail : detail?.name;
        if (target !== name) return;
        if (typeof detail === 'string') return; // dibuka ulang setelah error validasi: pertahankan isi
        this.editing = detail.mode === 'edit' ? detail.data?.id ?? null : null;
        this.form = { ...normalize(defaults), ...normalize(detail.data ?? {}) };
        this.saving = false;
        this.focusFirst();
    },

    /** Fokus langsung ke kolom isian pertama (bukan tombol tutup), agar operator bisa langsung mengetik/scan */
    focusFirst() {
        setTimeout(() => {
            const field = this.$el?.querySelector?.(
                '.modal-body input:not([type=hidden]):not([disabled]):not([readonly]), .modal-body select:not([disabled]), .modal-body textarea:not([disabled])',
            );
            field?.focus({ preventScroll: true });
        }, 260); // setelah transisi modal (200 ms) & jebakan fokus aktif, agar tidak ditimpa ke tombol tutup
    },
});
