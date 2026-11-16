<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DoorApiController;

// ---------------------------------------------------------------------
// Debug endpoint – returns UID yang tersimpan di cache pending_rfid_tap
// ---------------------------------------------------------------------
Route::get('/debug/pending-rfid', function () {
    return response()->json(
        Cache::get('pending_rfid_tap') ?? ['msg' => 'Cache kosong atau kadaluarsa']
    );
});

/*
|--------------------------------------------------------------------------
| API Routes untuk IoT ESP32 Smart Door
|--------------------------------------------------------------------------
| Jalur ini tidak memerlukan CSRF token, sehingga ESP32 dapat mengirimkan
| HTTP POST secara langsung.
*/
Route::prefix('door')->group(function () {
    Route::post('/check', [DoorApiController::class, 'check']);
    Route::post('/log', [DoorApiController::class, 'log']);
    Route::post('/state', [DoorApiController::class, 'state']);
});
