{{--
    Peringatan khusus mode demo/lokal: controller belum mengirim variabel yang dibutuhkan halaman.
    Halaman tetap tampil (dengan empty state) agar tidak crash; peringatan ini membantu tim saat integrasi.
    Tidak pernah tampil di produksi.
--}}
@props(['vars' => []])
@if (! empty($vars) && (config('app.ui_demo', app()->isLocal()) || config('app.debug')))
    <div class="mb-4 flex items-start gap-2.5 rounded-md border border-dashed border-warn-600 bg-warn-50 px-3.5 py-3 text-sm text-warn-700" role="note">
        <x-icon name="alert" :size="18" class="mt-0.5" />
        <p><span class="font-semibold">Untuk tim backend:</span> controller belum mengirim
            @foreach ($vars as $v)<span class="code">${{ $v }}</span>@if (! $loop->last), @endif @endforeach.
            Halaman ditampilkan dengan data kosong. Pesan ini tidak muncul di produksi.</p>
    </div>
@endif
