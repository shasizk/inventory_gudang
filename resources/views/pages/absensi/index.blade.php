@extends('layouts.app')
@section('title', 'Absensi Teknisi & NOC')
@section('section', 'Absensi')

@section('content')
    <x-page-header title="Absensi Teknisi & NOC" description="Check-in dan check-out dari lokasi tugas menggunakan kamera ponsel." />

    <div x-data="realTimeClock(@js(config('app.timezone')))" class="panel mb-6 flex flex-col gap-3 px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:px-5">
        <div>
            <p class="text-sm font-medium text-ink-500">Waktu saat ini</p>
            <time class="num font-display text-xl font-semibold tabular-nums text-ink-900" x-text="time" x-bind:datetime="now.toISOString()"></time>
            <p class="text-sm text-ink-500" x-text="date"></p>
        </div>
        <div class="flex flex-wrap gap-4 border-t border-concrete-200 pt-3 sm:border-t-0 sm:border-l sm:pt-0 sm:pl-5">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-ink-500">Masuk</p>
                <p class="num font-semibold text-ink-900">08:00</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-ink-500">Pulang</p>
                <p class="num font-semibold text-ink-900">{{ $checkoutStartTime }}</p>
            </div>
        </div>
    </div>

    @if ($canSubmit)
        <section class="panel mb-6 p-4 sm:p-5" x-data="attendanceCamera()">
            <div class="mb-4">
                <h2 class="text-lg font-semibold">Absensi hari ini</h2>
                <p class="mt-1 text-sm text-ink-500">Identitas dicatat dari akun login. Foto hanya menjadi bukti absensi; aplikasi tidak mengenali atau mencocokkan wajah.</p>
            </div>

            @if (! $todayAttendance)
                @php $action = route('absensi.check-in'); $actionLabel = 'Check-in'; @endphp
                <p class="mb-4 text-sm text-warn-700">Anda belum check-in hari ini.</p>
            @elseif (! $todayAttendance->check_out_at)
                @php $action = route('absensi.check-out'); $actionLabel = 'Check-out'; @endphp
                <p class="mb-4 text-sm text-ok-700">Check-in tercatat pukul {{ $todayAttendance->check_in_at->format('H:i') }}.</p>
                @if ($checkoutAllowed)
                    <p class="mb-4 text-sm text-ink-500">Check-out sudah tersedia. Ambil foto bukti untuk melanjutkan.</p>
                @else
                    <p class="mb-4 text-sm text-warn-700">Check-out dapat dilakukan mulai pukul {{ $checkoutStartTime }}.</p>
                @endif
            @else
                <p class="mb-4 text-sm text-ok-700">Absensi hari ini sudah lengkap. Check-in {{ $todayAttendance->check_in_at->format('H:i') }}, check-out {{ $todayAttendance->check_out_at->format('H:i') }}.</p>
            @endif

            @if (! $todayAttendance || (! $todayAttendance->check_out_at && $checkoutAllowed))
                <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <input x-ref="photo" class="sr-only" type="file" name="photo" accept="image/*" capture="user" x-on:change="selectPhoto($event)" required>

                    <div class="max-w-xl">
                        <img x-show="preview" x-cloak x-bind:src="preview" alt="Pratinjau foto bukti absensi" class="aspect-video w-full rounded-md bg-concrete-100 object-contain">
                        <div x-show="!preview" class="grid aspect-video place-items-center rounded-md border border-dashed border-concrete-300 bg-concrete-50 text-center text-sm text-ink-500">
                            Belum ada foto bukti.
                        </div>
                        <p x-show="error" x-cloak role="alert" class="mt-2 text-sm text-bad-700" x-text="error"></p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="btn btn-secondary" x-on:click="$refs.photo.click()">
                            <x-icon name="camera" :size="18" />
                            <span x-text="preview ? 'Ambil ulang foto' : 'Buka kamera / pilih foto'"></span>
                        </button>
                    </div>

                    <label class="flex max-w-2xl items-start gap-2 text-sm text-ink-700">
                        <input x-model="consented" type="checkbox" name="consent" value="1" class="mt-1">
                        <span>Saya setuju foto ini disimpan sebagai bukti absensi dan dapat dilihat oleh akun saya serta Owner. Dan foto ini dapat di pertanggungjawabkan jika tidak sesuai.</span>
                    </label>

                    <button type="submit" class="btn btn-primary" x-bind:disabled="!captured || !consented">
                        <x-icon name="check" :size="18" /> Simpan {{ $actionLabel }}
                    </button>
                </form>
            @endif
        </section>
    @endif

    <p class="mb-4 text-[15px] text-ink-700">
        <span class="num font-semibold">{{ $total }}</span> orang tercatat,
        <span class="num font-semibold text-ok-700">{{ $complete }}</span> lengkap,
        <span class="num font-semibold text-warn-700">{{ $total - $complete }}</span> belum check-out.
    </p>

    <x-table :rows="$attendance" empty-icon="clock"
             empty-title="Belum ada absensi pada tanggal ini" empty-text="Check-in dengan foto bukti akan tercatat di sini.">
        <x-slot:toolbar>
            <x-filter-bar placeholder="Cari nama" :search="$canViewAll">
                <label class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-ink-700">Tanggal</span>
                    <input type="date" name="date" value="{{ $attendanceDate }}" class="input w-auto">
                </label>
                @if ($canViewAll)
                    <select name="role" class="input w-auto" aria-label="Peran">
                        <option value="">Teknisi & NOC</option>
                        <option value="teknisi" @selected(request('role') === 'teknisi')>Teknisi</option>
                        <option value="noc" @selected(request('role') === 'noc')>NOC</option>
                    </select>
                @endif
            </x-filter-bar>
        </x-slot:toolbar>
        <x-slot:head>
            <tr>
                <th>Nama</th>
                <th>Tanggal</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th class="text-right">Durasi</th>
                <th>Bukti foto</th>
                <th>Status</th>
            </tr>
        </x-slot:head>

        @foreach ($attendance as $a)
            <tr>
                <td><x-cell.person :name="$a->user->name" :role="$a->user->role" /></td>
                <td class="text-ink-500">{{ $a->attendance_date->format('d/m/Y') }}</td>
                <td class="font-semibold text-in-700"><x-cell.datetime :value="$a->check_in_at" mode="time" /></td>
                <td @class(['font-semibold', 'text-out-700' => filled($a->check_out_at)])><x-cell.datetime :value="$a->check_out_at" mode="time" empty="Belum check-out" /></td>
                <td class="text-right"><x-cell.duration :from="$a->check_in_at" :to="$a->check_out_at" /></td>
                <td class="whitespace-nowrap text-sm">
                    <a class="text-in-700 underline" href="{{ route('absensi.photo', [$a, 'check-in']) }}" target="_blank" rel="noopener">Masuk</a>
                    @if ($a->check_out_photo)
                        <span class="text-ink-400">/</span>
                        <a class="text-in-700 underline" href="{{ route('absensi.photo', [$a, 'check-out']) }}" target="_blank" rel="noopener">Keluar</a>
                    @endif
                </td>
                <td>
                    @if ($a->check_out_at)
                        <x-status-badge status="success">Lengkap</x-status-badge>
                    @else
                        <x-status-badge status="warning">Belum check-out</x-status-badge>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-table>
@endsection
