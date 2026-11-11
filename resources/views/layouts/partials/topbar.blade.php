@php
    $user = auth()->user();
    $role = $currentRole ?? 'guest';
    $isDemo = config('app.ui_demo', app()->isLocal());
    $displayName = $user->name ?? $user->username ?? ($isDemo ? 'Dewi Lestari' : 'Pengguna');
    $initials = \Illuminate\Support\Str::of($displayName)->squish()->explode(' ')->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->join('') ?: '?';
    $roleLabel = ['owner' => 'Owner', 'admin' => 'Admin', 'teknisi' => 'Teknisi', 'noc' => 'NOC'][$role] ?? 'Belum masuk';
@endphp
{{-- Latar solid (tanpa backdrop-blur): blur dihitung ulang tiap frame & memberatkan komputer tanpa akselerasi grafis --}}
<header class="sticky top-0 z-30 border-b border-concrete-300/70 bg-concrete-100">
    <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
        <button type="button" class="btn btn-ghost btn-icon -ml-1 lg:hidden" x-on:click="nav = true" aria-label="Buka menu">
            <x-icon name="menu" />
        </button>

        <p class="min-w-0 truncate text-sm font-medium text-ink-500">@yield('section', 'Ringkasan')</p>

        <div class="ml-auto flex items-center gap-2">
            {{-- Indikator koneksi pintu, selalu terlihat di semua halaman --}}
            <div class="hidden min-w-[220px] items-center justify-end gap-2 md:flex" x-data>
                {{-- Placeholder ukuran tetap: mencegah elemen topbar bergeser saat status pertama dimuat --}}
                <template x-if="!$store.door.doors.length">
                    <span class="flex gap-2" aria-hidden="true"><span class="skeleton h-9 w-32 rounded-md"></span><span class="skeleton h-9 w-32 rounded-md"></span></span>
                </template>
                <template x-for="d in $store.door.doors" :key="d.id">
                    <span class="inline-flex h-9 items-center gap-2 rounded-md border border-concrete-300 bg-white px-2.5 text-sm"
                          x-bind:title="d.name + ': ' + $store.door.stateLabel(d)">
                        <span class="relative flex size-2.5">
                            <span class="relative size-2.5 rounded-full" x-bind:class="!d.online ? 'bg-bad-600' : (d.pending ? 'bg-hazard-500 ring-1 ring-hazard-700' : 'bg-ok-600')"></span>
                        </span>
                        <span class="font-semibold" x-text="d.short ?? d.name"></span>
                        <span class="text-ink-500" x-text="$store.door.stateLabel(d)"></span>
                    </span>
                </template>
                <span x-show="$store.door.error && $store.door.doors.length" x-cloak class="badge badge-warn" title="Data status pintu mungkin tidak terbaru">Data tertunda</span>
            </div>

            {{-- HP: status pintu ringkas (titik warna + nama singkat), detail di dashboard --}}
            <a href="{{ route('dashboard') }}#door-title" class="flex items-center gap-2.5 rounded-md px-2 py-1.5 text-[13px] font-semibold md:hidden" x-data
               x-show="$store.door.doors.length" x-cloak aria-label="Status pintu">
                <template x-for="d in $store.door.doors" :key="d.id">
                    <span class="flex items-center gap-1" x-bind:title="d.name + ': ' + $store.door.stateLabel(d)">
                        <span class="size-2 rounded-full" x-bind:class="!d.online ? 'bg-bad-600' : (d.pending ? 'bg-hazard-500 ring-1 ring-hazard-700' : 'bg-ok-600')"></span>
                        <span x-text="d.short"></span>
                    </span>
                </template>
            </a>

            @if ($isDemo)
                {{-- Hanya muncul di mode demo: uji tampilan per peran tanpa ganti akun --}}
                <label class="hidden items-center gap-2 text-sm text-ink-500 sm:flex">
                    <span>Lihat sebagai</span>
                    <select class="input h-9 w-auto py-0 pr-8 text-sm"
                            x-data x-on:change="const u = new URL(location.href); u.searchParams.set('as', $event.target.value); location.href = u">
                        @foreach (['owner' => 'Owner', 'admin' => 'Admin', 'teknisi' => 'Teknisi', 'noc' => 'NOC'] as $value => $label)
                            <option value="{{ $value }}" @selected($role === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
            @endif

            <div class="relative" x-data="{ open: false }" x-on:click.outside="open = false" x-on:keydown.escape="open = false">
                <button type="button" class="flex items-center gap-2.5 rounded-md py-1 pr-2 pl-1 hover:bg-concrete-200/70" x-on:click="open = !open" x-bind:aria-expanded="open" aria-haspopup="menu">
                @if ($user?->photo_url)
                    <img src="{{ $user->photo_url }}" alt="" class="size-8 rounded-md object-cover">
                @else
                    <span class="grid size-8 place-items-center rounded-md bg-ink-900 text-sm font-semibold text-white">{{ $initials }}</span>
                @endif
                    <span class="hidden text-left leading-tight sm:block">
                        <span class="block text-sm font-semibold">{{ $displayName }}</span>
                        <span class="block text-[13px] text-ink-500">{{ $roleLabel }}</span>
                    </span>
                </button>
                <div x-show="open" x-transition.origin.top.right x-cloak class="absolute right-0 mt-2 w-52 overflow-hidden rounded-lg border border-concrete-300 bg-white py-1 shadow-[0_12px_32px_-12px_rgb(14_26_38/0.4)]" role="menu">
                   @if ($user)
                        <div class="border-b border-concrete-200 px-3.5 py-2.5">
                            <p class="truncate text-sm font-semibold">{{ $user->name }}</p>
                            <p class="truncate text-[13px] text-ink-500">{{ $user->email }}</p>
                        </div>
                    @endif
                    @if (Route::has('profile.edit'))
                        <a href="{{ route('profile.edit') }}" class="flex w-full items-center gap-2.5 px-3.5 py-2 hover:bg-concrete-100" role="menuitem">
                            <x-icon name="id-card" :size="18" /> My Profile
                        </a>
                        <div class="my-1 border-t border-concrete-200" role="separator"></div>
                    @endif
                    @if (Route::has('logout'))
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2.5 px-3.5 py-2 text-left hover:bg-concrete-100" role="menuitem">
                                <x-icon name="logout" :size="18" /> Keluar dari akun
                            </button>
                        </form>
                    @else
                        <p class="px-3.5 py-2 text-sm text-ink-500">Route logout belum tersedia.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</header>
