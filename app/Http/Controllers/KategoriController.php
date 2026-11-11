<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\AutoNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class KategoriController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $categories = Category::query()
            ->withCount(['serialNumbers as items_count'])
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w
                ->where('code', 'like', "%{$q}%")
                ->orWhere('name', 'like', "%{$q}%")))
            ->orderBy('code')
            ->paginate(15)
            ->withQueryString();

        return view('pages.barang-masuk.kategori', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->authorizeManage();
        $data = $this->validated($request);

        // Kode TIDAK diambil dari input; selalu dibuat server. Satu transaksi dengan counter.
        DB::transaction(function () use ($data) {
            Category::create($data + ['code' => AutoNumber::next('kategori', $this->actor())]);
        });

        return back()->with('success', 'Kategori disimpan.');
    }

    public function update(Request $request, int $id)
    {
        $this->authorizeManage();
        $category = Category::findOrFail($id);

        // Kode tidak bisa diubah karena dipakai di serial number.
        $category->update($this->validated($request, $category->id));

        return back()->with('success', 'Kategori diperbarui.');
    }

    public function destroy(int $id)
    {
        $this->authorizeManage();
        $category = Category::findOrFail($id);

       if ($category->serialNumbers()->exists()) {
            return back()->with('error', 'Kategori masih dipakai serial number. Hapus atau pindahkan unitnya terlebih dahulu.');
        }

        $category->delete();

        return back()->with('success', 'Kategori dihapus.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'        => ['required', 'string', 'max:80', Rule::unique('categories', 'name')->ignore($ignoreId)],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required'   => 'Nama kategori wajib diisi.',
            'name.max'        => 'Nama kategori maksimal 80 karakter.',
            'name.unique'     => 'Nama kategori sudah dipakai.',
            'description.max' => 'Deskripsi maksimal 255 karakter.',
        ]);
    }

    /** Sementara meniru logika View::composer di routes/frontend.php. Ganti dengan middleware/policy setelah login siap. */
    private function authorizeManage(): void
    {
        $role = strtolower((string) (auth()->user()->role ?? (config('app.ui_demo', app()->isLocal()) ? 'owner' : 'guest')));
        abort_unless(in_array($role, ['owner', 'admin'], true), 403, 'Anda tidak punya hak mengelola kategori.');
    }

    private function actor(): ?string
    {
        $user = auth()->user();

        return $user ? Str::limit((string) ($user->username ?? $user->name), 20, '') : null;
    }
}