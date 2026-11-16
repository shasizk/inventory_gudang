<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\SerialNumberController;
use App\Http\Controllers\RfidCardController;
use App\Http\Controllers\DoorLogController;
use App\Http\Controllers\Api\DoorApiController;

// Login, register, OTP, logout, My Profile
require __DIR__ . '/auth.php';

// Semua halaman aplikasi wajib login
Route::middleware('auth')->group(function () {
    // Manajemen Kartu RFID (CRUD)
    Route::prefix('user-management/kartu')->controller(RfidCardController::class)->group(function () {
        Route::get('/', 'index')->name('cards.index');
        Route::post('/', 'store')->name('cards.store');
        Route::put('/{id}', 'update')->name('cards.update');
        Route::delete('/{id}', 'destroy')->name('cards.destroy');
    });

    // Riwayat Akses Pintu Smart Lock
    Route::get('/history/pintu', [DoorLogController::class, 'index'])->name('history.door');
    Route::get('/ajax/door/logs/latest', [DoorLogController::class, 'latest'])->name('ajax.door.logs.latest');

    // AJAX Endpoint untuk Capture UID Kartu RFID secara Realtime di Modal Web
    Route::get('/ajax/rfid/pending', [DoorApiController::class, 'pending'])->name('ajax.rfid.pending');

    Route::prefix('barang-masuk/kategori')->controller(KategoriController::class)->group(function () {
        Route::get('/', 'index')->name('kategori.index');
        Route::post('/', 'store')->name('kategori.store');
        Route::put('/{id}', 'update')->name('kategori.update');
        Route::delete('/{id}', 'destroy')->name('kategori.destroy');
    });

    Route::prefix('barang-masuk/serial-number')->controller(SerialNumberController::class)->group(function () {
        Route::get('/', 'index')->name('serial.index');
        Route::get('/label', 'label')->name('serial.label');
        Route::get('/label-semua', 'labelAll')->name('serial.label.all');
        Route::post('/', 'store')->name('serial.store');
        Route::delete('/{id}', 'destroy')->name('serial.destroy');
    });

    require __DIR__ . '/frontend.php';
});