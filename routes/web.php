<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\SerialNumberController;

// Login, register, OTP, logout, My Profile
require __DIR__ . '/auth.php';

// Semua halaman aplikasi wajib login
Route::middleware('auth')->group(function () {
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