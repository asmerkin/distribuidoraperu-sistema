<?php

use App\Http\Controllers\ScannerApiController;
use Illuminate\Support\Facades\Route;

Route::post('/scanner/auth', [ScannerApiController::class, 'authenticate']);

Route::middleware('auth:scanner')->prefix('scanner')->group(function () {
    Route::get('/device', [ScannerApiController::class, 'device']);
    Route::get('/lookup', [ScannerApiController::class, 'lookup']);
    Route::post('/adjust', [ScannerApiController::class, 'adjust']);
    Route::post('/quick-adjust', [ScannerApiController::class, 'quickAdjust']);
});
