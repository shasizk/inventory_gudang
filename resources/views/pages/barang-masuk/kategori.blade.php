{{-- $categories : koleksi/paginator {id, code, name, description, items_count} --}}
@extends('layouts.app')
@section('title', 'Kategori barang')
@section('section', 'Barang masuk')

@section('content')
    @php
        // Pengaman: bila controller lupa mengirim variabel, halaman tetap tampil dengan data kosong (bukan error 500)
        $__missing = array_values(array_diff(['categories'], array_keys(get_defined_vars())));
        $categories ??= collect();
    @endphp
    <x-dev-missing :vars="$__missing" />
    <x-page-header title="Kategori barang" description="Kelompok barang untuk pencarian, kode serial number, dan laporan stok.">
        @if ($canManage)
            <x-slot:actions>
                <x-button icon="plus" x-on:click="$dispatch('open-modal', { name: 'kategori-form', mode: 'create' })">Tambah kategori</x-button>
            </x-slot:actions>
        @endif
    </x-page-header>

    <x-table :rows="$categories" empty-icon="tag"
                  empty-title="Belum ada kategori" empty-text="Buat kategori pertama agar barang masuk bisa dikelompokkan, misalnya Perangkat jaringan atau Kabel & konektor.">
        <x-slot:toolbar>
            <x-filter-bar placeholder="Cari kode atau nama kategori" />
        </x-slot:toolbar>
        <x-slot:head>
            <tr>
                <th class="w-28">No. Kategori</th>
                <th>Nama kategori</th>
                <th>Deskripsi</th>
                @if ($canManage)<th class="w-px"><span class="sr-only">Aksi</span></th>@endif
            </tr>
        </x-slot:head>

        @foreach ($categories as $category)
            <tr>
                <td><x-cell.code :value="$category->code" class="rounded bg-concrete-100 px-1.5 py-0.5" /></td>
                <td><x-cell.text :value="$category->name" max="28ch" strong /></td>
                <td><x-cell.text :value="$category->description ?: '-'" max="48ch" lines="2" class="text-ink-500" /></td>
                @if ($canManage)
                    <td>
                        <div class="flex justify-end gap-1">
                            <button type="button" class="btn btn-ghost btn-icon" title="Ubah {{ $category->name }}" aria-label="Ubah {{ $category->name }}"
                                    x-on:click="$dispatch('open-modal', { name: 'kategori-form', mode: 'edit', data: @js(['id' => $category->id, 'code' => $category->code, 'name' => $category->name, 'description' => $category->description]) })">
                                <x-icon name="edit" :size="18" />
                            </button>
                            <button type="button" class="btn btn-ghost btn-icon hover:bg-bad-50! hover:text-bad-700!" title="Hapus {{ $category->name }}" aria-label="Hapus {{ $category->name }}"
                                    x-on:click="$dispatch('confirm-delete', { url: @js(route('kategori.destroy', $category->id)), label: @js($category->name), note: @js((int) ($category->items_count ?? 0) > 0 ? 'Kategori ini dipakai ' . (int) $category->items_count . ' barang. Pindahkan barangnya ke kategori lain terlebih dahulu.' : null) })">
                                <x-icon name="trash" :size="18" />
                            </button>
                        </div>
                    </td>
                @endif
            </tr>
        @endforeach

        @if ($canManage)
            <x-slot:empty-action>
                <x-button icon="plus" x-on:click="$dispatch('open-modal', { name: 'kategori-form', mode: 'create' })">Tambah kategori</x-button>
            </x-slot:empty-action>
        @endif
    </x-table>
@endsection

@push('modals')
    <x-modal name="kategori-form" size="md">
        <form method="POST" x-bind:action="action" x-on:submit="guard($event)"
              x-data="crudForm({
                  name: 'kategori-form',
                  storeUrl: @js(route('kategori.store')),
                  updateUrl: @js(route('kategori.update', '__ID__')),
                  defaults: { code: '', name: '', description: '' },
                  old: @js(old() ?: null),
                  reopen: @js($errors->any()),
              })"
              x-on:open-modal.window="open($event.detail)">
            @csrf
            <template x-if="editing"><input type="hidden" name="_method" value="PUT"></template>
            <input type="hidden" name="_editing" x-bind:value="editing ?? ''">

            <header class="modal-head">
                <h2 class="modal-title" x-text="editing ? 'Ubah kategori' : 'Tambah kategori'"></h2>
                <x-modal-close name="kategori-form" />
            </header>
            <div class="modal-body">
                <div class="grid gap-4 sm:grid-cols-[140px_1fr]">
                        <x-field label="KATEGORI_NO" name="code" hint="Dibuat otomatis oleh sistem">
                            <input id="code" x-model="form.code" readonly tabindex="-1"
                                class="input code uppercase bg-concrete-100" placeholder="Otomatis">
                        </x-field>
                    <x-field label="Nama kategori" name="name" required>
                        <input id="name" name="name" x-model="form.name" maxlength="80" required class="input @error('name') input-error @enderror" placeholder="Perangkat jaringan">
                    </x-field>
                </div>
                <x-field label="Deskripsi" name="description" hint="Opsional. Membantu operator memilih kategori yang tepat.">
                    <textarea id="description" name="description" x-model="form.description" rows="3" maxlength="255" class="input @error('description') input-error @enderror" placeholder="Router, switch, access point, modul SFP"></textarea>
                </x-field>
            </div>
            <footer class="modal-foot">
                <x-button variant="secondary" x-on:click="$dispatch('close-modal', 'kategori-form')">Batal</x-button>
                <x-button type="submit" loading="saving"><span x-text="editing ? 'Simpan perubahan' : 'Simpan kategori'"></span></x-button>
            </footer>
        </form>
    </x-modal>
@endpush
