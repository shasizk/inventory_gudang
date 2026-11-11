<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

$demoRoles = ['owner', 'admin', 'teknisi', 'noc'];

/*
| Variabel peran untuk semua view: $currentRole, $isOwner, $canManage.
| Mode demo: ?as=teknisi untuk mencoba tampilan per peran tanpa login.
| (Boleh dipindah ke AppServiceProvider::boot oleh tim backend.)
*/
$isDemo = fn () => (bool) config('app.ui_demo', app()->isLocal());

View::composer('*', function ($view) use ($demoRoles, $isDemo) {
    // Di luar mode demo, pengunjung yang belum login dianggap "guest" (tanpa hak apa pun), BUKAN Owner.
    $role = strtolower((string) (auth()->user()->role ?? ($isDemo() ? 'owner' : 'guest')));
    if (! in_array($role, $demoRoles, true)) {
        $role = 'guest';
    }

    if ($isDemo()) {
        if (in_array(request('as'), $demoRoles, true)) {
            session(['ui_as' => request('as')]);
        }
        $role = session('ui_as', $role);
    }

    $view->with([
        'currentRole' => $role,
        'isOwner' => $role === 'owner',
        'canManage' => in_array($role, ['owner', 'admin'], true),
    ]);
});

/* ---------- Dummy data ---------- */
$rows = fn (array $items) => collect($items)->map(fn ($item) => (object) $item);

$categories = $rows([
    ['id' => 1, 'code' => 'RTR', 'name' => 'Perangkat jaringan', 'description' => 'Router, switch, access point, modul SFP', 'items_count' => 4],
    ['id' => 2, 'code' => 'KBL', 'name' => 'Kabel & konektor', 'description' => 'Kabel UTP, fiber optik, patch cord, konektor RJ45', 'items_count' => 3],
    ['id' => 3, 'code' => 'ONT', 'name' => 'Perangkat FTTH', 'description' => 'ONT, ODP, splitter', 'items_count' => 2],
    ['id' => 4, 'code' => 'TLS', 'name' => 'Peralatan kerja', 'description' => 'Tang crimping, LAN tester, OPM', 'items_count' => 0],
]);

$stocks = $rows([
    ['id' => 1, 'name' => 'Router MikroTik hEX RB750Gr3', 'category' => 'Perangkat jaringan', 'total_in' => 30, 'total_out' => 18, 'stock' => 12, 'min_stock' => 5, 'unit' => 'unit'],
    ['id' => 2, 'name' => 'Kabel UTP Cat6 roll 305 m', 'category' => 'Kabel & konektor', 'total_in' => 10, 'total_out' => 6, 'stock' => 4, 'min_stock' => 3, 'unit' => 'roll'],
    ['id' => 3, 'name' => 'ONT ZTE F670L', 'category' => 'Perangkat FTTH', 'total_in' => 24, 'total_out' => 24, 'stock' => 0, 'min_stock' => 4, 'unit' => 'unit'],
    ['id' => 4, 'name' => 'Modul SFP 1.25G 20 km', 'category' => 'Perangkat jaringan', 'total_in' => 40, 'total_out' => 13, 'stock' => 27, 'min_stock' => 6, 'unit' => 'unit'],
    ['id' => 5, 'name' => 'Konektor RJ45 Cat6 (isi 100)', 'category' => 'Kabel & konektor', 'total_in' => 15, 'total_out' => 13, 'stock' => 2, 'min_stock' => 5, 'unit' => 'pak'],
    ['id' => 6, 'name' => 'Switch TP-Link TL-SG108', 'category' => 'Perangkat jaringan', 'total_in' => 12, 'total_out' => 3, 'stock' => 9, 'min_stock' => 3, 'unit' => 'unit'],
]);

$movements = $rows([
    ['id' => 9, 'at' => now()->subMinutes(4), 'item_name' => 'Router MikroTik hEX RB750Gr3', 'serial' => 'SN-RTR-261021-A7K2', 'type' => 'out', 'qty' => 1, 'stock_after' => 12, 'user' => 'Rizky Pratama', 'role' => 'teknisi'],
    ['id' => 8, 'at' => now()->subMinutes(25), 'item_name' => 'Modul SFP 1.25G 20 km', 'serial' => 'SN-SFP-260930-L3P8', 'type' => 'in', 'qty' => 10, 'stock_after' => 27, 'user' => 'Dewi Lestari', 'role' => 'owner'],
    ['id' => 7, 'at' => now()->subHours(2), 'item_name' => 'ONT ZTE F670L', 'serial' => 'SN-ONT-261002-Q9M1', 'type' => 'out', 'qty' => 1, 'stock_after' => 0, 'user' => 'Fajar Nugroho', 'role' => 'noc'],
    ['id' => 6, 'at' => now()->subHours(3), 'item_name' => 'Kabel UTP Cat6 roll 305 m', 'serial' => '8991002105539', 'type' => 'out', 'qty' => 1, 'stock_after' => 4, 'user' => 'Andi Saputra', 'role' => 'admin'],
    ['id' => 5, 'at' => now()->subDay(), 'item_name' => 'Switch TP-Link TL-SG108', 'serial' => '6935364072339', 'type' => 'in', 'qty' => 5, 'stock_after' => 9, 'user' => 'Dewi Lestari', 'role' => 'owner'],
]);

$attendance = $rows([
    ['name' => 'Rizky Pratama', 'role' => 'teknisi', 'date' => now(), 'first_tap' => now()->setTime(7, 52), 'last_tap' => null, 'door' => 'gudang'],
    ['name' => 'Fajar Nugroho', 'role' => 'noc', 'date' => now(), 'first_tap' => now()->setTime(7, 58), 'last_tap' => now()->setTime(16, 5), 'door' => 'ict'],
    ['name' => 'Bima Aditya', 'role' => 'teknisi', 'date' => now(), 'first_tap' => now()->setTime(8, 14), 'last_tap' => now()->setTime(12, 40), 'door' => 'gudang'],
    ['name' => 'Sinta Maharani', 'role' => 'noc', 'date' => now(), 'first_tap' => now()->setTime(13, 2), 'last_tap' => null, 'door' => 'ict'],
]);

$doorLogs = $rows([
    ['id' => 6, 'at' => now()->subMinutes(1), 'name' => 'Dewi Lestari', 'role' => 'owner', 'door' => 'gudang', 'direction' => 'in', 'method' => 'rfid', 'companion' => null, 'result' => 'granted', 'reason' => null],
    ['id' => 5, 'at' => now()->subMinutes(10), 'name' => 'Kartu tidak dikenal', 'role' => null, 'door' => 'ict', 'direction' => 'in', 'method' => 'rfid', 'companion' => null, 'result' => 'denied', 'reason' => 'UID 4F A1 09 C2 belum terdaftar'],
    ['id' => 4, 'at' => now()->subMinutes(45), 'name' => 'Fajar Nugroho', 'role' => 'noc', 'door' => 'ict', 'direction' => 'in', 'method' => 'two_step', 'companion' => 'Andi Saputra (Admin)', 'result' => 'granted', 'reason' => null],
    ['id' => 3, 'at' => now()->subHours(2), 'name' => 'Rizky Pratama', 'role' => 'teknisi', 'door' => 'gudang', 'direction' => 'in', 'method' => 'two_step', 'companion' => null, 'result' => 'denied', 'reason' => 'Waktu tunggu tap Admin/Owner habis (30 detik)'],
    ['id' => 2, 'at' => now()->subHours(3), 'name' => 'Andi Saputra', 'role' => 'admin', 'door' => 'gudang', 'direction' => 'out', 'method' => 'rfid', 'companion' => null, 'result' => 'granted', 'reason' => null],
]);

/* ---------- Masuk & keluar akun (stub pratinjau) ----------
| Backend: ganti dengan controller autentikasi (mis. Breeze/Fortify) yang menerima field
| `login` (username ATAU email), `password`, `remember`, lalu lindungi rute lain dengan ->middleware('auth').
*/
// Route::view('/login', 'pages.auth.login')->name('login');

// Route::post('/login', function () use ($demoRoles, $isDemo) {
//     // DIPAKE BACKEND
//     // Pengaman produksi: stub ini menerima kata sandi apa pun, jadi hanya boleh aktif di mode demo
//     // if (! $isDemo()) {
//     //     return back()->withErrors(['login' => 'Login belum tersambung ke backend. Hubungi tim backend.'])->onlyInput('login');
//     // }
//     // $data = request()->validate(
//     //     ['login' => 'required|string', 'password' => 'required|string'],
//     //     ['login.required' => 'Isi username atau email.', 'password.required' => 'Isi kata sandi.'],
//     // );
//     // // Pemicu untuk menguji tampilan error di mode pratinjau
//     // if ($data['password'] === 'salah') {
//     //     return back()->withErrors(['login' => 'Username atau kata sandi salah.'])->onlyInput('login');
//     // }
//     // if ($data['password'] === 'kunci') {
//     //     return back()->withErrors(['login' => 'Akun dikunci sementara.'])->onlyInput('login')->with('throttle_seconds', 30);
//     // }
//     // $role = in_array(request('demo_role'), $demoRoles, true) ? request('demo_role') : 'owner';
//     // session(['ui_as' => $role]);
//     // request()->session()->regenerate();

//     // return redirect()->intended(route('dashboard'))->with('success', 'Berhasil masuk sebagai ' . ($role === 'noc' ? 'NOC' : ucfirst($role)) . ' (mode pratinjau).');
// });

// Route::post('/logout', function () {
//     //DIPAKE BACKEND
//     // session()->forget('ui_as');
//     // request()->session()->invalidate();
//     // request()->session()->regenerateToken();

//     // return redirect()->route('login')->with('success', 'Anda sudah keluar dari akun.');
// })->name('logout');

/* ---------- Halaman ---------- */
// Route::redirect('/', '/dashboard'); DIPAKE BACKEND

Route::get('/dashboard', fn () => view('pages.dashboard', [
    'stats' => [
        'stock_total' => $stocks->sum('stock'),
        'sku' => $stocks->count(),
        'in_today' => 10,
        'out_today' => 3,
        'low_stock' => $stocks->filter(fn ($s) => $s->stock <= $s->min_stock)->count(), // habis + di bawah minimum
        'people_inside' => 2,
    ],
    'lowStock' => $stocks->filter(fn ($s) => $s->stock <= $s->min_stock)->sortBy('stock')->values(),
]))->name('dashboard');

 Route::prefix('barang-masuk')->group(function () use ($rows, $categories) {
    //dipake backend
    // Route::get('/kategori', fn () => view('pages.barang-masuk.kategori', ['categories' => $categories]))->name('kategori.index');

    // Route::get('/serial-number', fn () => view('pages.barang-masuk.serial-number', [
    // //dipake backend
    // ]))->name('serial.index');

    Route::get('/', fn () => view('pages.barang-masuk.index', [
        'categories' => $categories,
        //dipake backend
    ]))->name('barang-masuk.index');
});

Route::prefix('barang-keluar')->group(function () use ($stocks, $movements) {
    Route::get('/stok', function () use ($stocks) {
        $state = fn ($s) => $s->stock === 0 ? 'habis' : ($s->stock <= $s->min_stock ? 'menipis' : 'aman');
        $counts = $stocks->countBy($state);

        return view('pages.barang-keluar.stok', [
            'stocks' => request('status') ? $stocks->filter(fn ($s) => $state($s) === request('status'))->values() : $stocks,
            'summary' => ['all' => $stocks->count(), 'aman' => $counts['aman'] ?? 0, 'menipis' => $counts['menipis'] ?? 0, 'habis' => $counts['habis'] ?? 0],
        ]);
    })->name('stok.index');

    Route::get('/', fn () => view('pages.barang-keluar.index', [
        'outgoing' => $movements->where('type', 'out')->map(fn ($m) => (object) [
            ...(array) $m,
            'category' => 'Perangkat jaringan',
            'source' => $m->id % 2 ? 'scanner' : 'manual',
        ])->values(),
    ]))->name('barang-keluar.index');
});

Route::get('/absensi', fn () => view('pages.absensi.index', ['attendance' => $attendance]))->name('absensi.index');

Route::prefix('user-management')->group(function () use ($rows) {
    Route::get('/kartu', fn () => view('pages.users.cards', ['cards' => $rows([
        ['id' => 1, 'uid' => 'A3F2197C', 'name' => 'Dewi Lestari', 'role' => 'owner', 'active' => true, 'created_at' => now()->subMonths(2), 'last_used_at' => now()->subMinutes(1)],
        ['id' => 2, 'uid' => '5B0E44D1', 'name' => 'Andi Saputra', 'role' => 'admin', 'active' => true, 'created_at' => now()->subMonths(2), 'last_used_at' => now()->subHours(3)],
        ['id' => 3, 'uid' => 'C7129A3E', 'name' => 'Rizky Pratama', 'role' => 'teknisi', 'active' => true, 'created_at' => now()->subMonth(), 'last_used_at' => now()->subHours(2)],
        ['id' => 4, 'uid' => '0D8F6B21', 'name' => 'Bima Aditya', 'role' => 'teknisi', 'active' => false, 'created_at' => now()->subMonth(), 'last_used_at' => null],
    ])]))->name('cards.index');

    Route::get('/akun', fn () => view('pages.users.index', ['users' => $rows([
        ['id' => 1, 'username' => 'dewi.owner', 'email' => 'dewi@gudang.co.id', 'role' => 'owner', 'last_login_at' => now()->subMinutes(30)],
        ['id' => 2, 'username' => 'andi.admin', 'email' => 'andi@gudang.co.id', 'role' => 'admin', 'last_login_at' => now()->subDay()],
        ['id' => 3, 'username' => 'rizky.teknisi', 'email' => 'rizky@gudang.co.id', 'role' => 'teknisi', 'last_login_at' => now()->subHours(2)],
        ['id' => 4, 'username' => 'fajar.noc', 'email' => 'fajar@gudang.co.id', 'role' => 'noc', 'last_login_at' => null],
    ])]))->name('users.index');
});

Route::get('/laporan', fn () => view('pages.laporan.index', [
    'categories' => $categories,
    'report' => ['stok' => $stocks, 'mutasi' => $movements, 'absensi' => $attendance, 'akses' => $doorLogs],
]))->name('laporan.index');
Route::get('/laporan/export', fn () => response()->json(['message' => 'Ekspor belum diimplementasikan backend.'], 501))->name('laporan.export');

Route::prefix('history')->name('history.')->group(function () use ($rows, $doorLogs, $movements) {
    Route::get('/pintu', fn () => view('pages.history.door', ['logs' => $doorLogs]))->name('door');
    Route::get('/barang', fn () => view('pages.history.barang', ['movements' => $movements]))->name('barang');
    Route::get('/audit', fn () => view('pages.history.audit', ['audits' => $rows([
        ['id' => 5, 'at' => now()->subMinutes(4), 'user' => 'Rizky Pratama', 'role' => 'teknisi', 'module' => 'Barang keluar', 'action' => 'scan', 'description' => 'Scan SN-RTR-261021-A7K2, stok Router MikroTik hEX menjadi 12', 'ip' => '192.168.10.24'],
        ['id' => 4, 'at' => now()->subMinutes(10), 'user' => 'ESP32 Ruang ICT', 'role' => null, 'module' => 'Smart Lock', 'action' => 'door', 'description' => 'Akses ditolak: UID 4F A1 09 C2 belum terdaftar', 'ip' => null],
        ['id' => 3, 'at' => now()->subMinutes(25), 'user' => 'Dewi Lestari', 'role' => 'owner', 'module' => 'Barang masuk', 'action' => 'create', 'description' => 'Mencatat 10 unit Modul SFP 1.25G 20 km dari PT Citra Jaringan', 'ip' => '192.168.10.5'],
        ['id' => 2, 'at' => now()->subHours(1), 'user' => 'Andi Saputra', 'role' => 'admin', 'module' => 'Kartu RFID', 'action' => 'update', 'description' => 'Memblokir kartu Bima Aditya (0D 8F 6B 21)', 'ip' => '192.168.10.7'],
        ['id' => 1, 'at' => now()->subHours(2), 'user' => 'Dewi Lestari', 'role' => 'owner', 'module' => 'Akun web', 'action' => 'login', 'description' => 'Masuk ke website', 'ip' => '192.168.10.5'],
    ])]))->name('audit');
});

/* ---------- Stub aksi form (POST/PUT/DELETE) ---------- */
$resources = [
    //dipake backend
    // 'kategori' => 'barang-masuk/kategori',
    // 'serial' => 'barang-masuk/serial-number',
    'barang-masuk' => 'barang-masuk',
    'cards' => 'user-management/kartu',
    'users' => 'user-management/akun',
];
$stub = fn (string $message) => fn () => $isDemo()
    ? back()->with('success', $message)
    : back()->with('error', 'Fitur ini belum tersambung ke backend, data tidak disimpan.');
foreach ($resources as $name => $path) {
    Route::post($path, $stub('Data tersimpan (mode pratinjau, belum masuk database).'))->name("$name.store");
    Route::put("$path/{id}", $stub('Perubahan tersimpan (mode pratinjau).'))->name("$name.update");
    Route::delete("$path/{id}", $stub('Data dihapus (mode pratinjau).'))->name("$name.destroy");
}

/*
| Endpoint JSON untuk komponen interaktif. Kontrak request/response: docs/kontrak-payload.md
| Prefix "ajax" (bukan "api") agar memakai sesi login & CSRF web, terpisah dari routes/api.php milik ESP32.
| Saat ui_demo aktif, front-end tidak memanggil endpoint ini (dijawab simulator JS).
*/
Route::prefix('ajax')->name('ajax.')->group(function () {
    $notYet = fn () => response()->json(['message' => 'Endpoint belum diimplementasikan backend.'], 501);
    Route::get('/door/status', $notYet)->name('door.status');
    Route::get('/rfid/pending', $notYet)->name('rfid.pending');
    Route::get('/activity/latest', $notYet)->name('activity.latest');
    Route::get('/stats/circulation', $notYet)->name('stats.circulation');
    Route::get('/barang/lookup', $notYet)->name('barang.lookup');
    Route::post('/barang/keluar/scan', $notYet)->name('barang.keluar.scan');
});
