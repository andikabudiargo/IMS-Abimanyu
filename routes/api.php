<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ReceivingController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('receiving/scan-chemical-unit', [ReceivingController::class, 'scanChemicalUnit'])
        ->name('receiving.scanChemicalUnit');
    Route::post('receiving/extend-expired-date', [ReceivingController::class, 'extendExpiredDate'])
        ->name('receiving.extendExpiredDate');
});