<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\ReceivingController;
use App\Http\Controllers\TransferStockController;
use App\Http\Controllers\Api\StoController;

Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('receiving/scan-chemical-unit', [ReceivingController::class, 'scanChemicalUnit'])
        ->name('receiving.scanChemicalUnit');
    Route::post('receiving/extend-expired-date', [ReceivingController::class, 'extendExpiredDate'])
        ->name('receiving.extendExpiredDate');
Route::get('transfer/locations',          [TransferStockController::class, 'apiLocations']);
Route::get('transfer/articles',           [TransferStockController::class, 'articleByLocation']);
Route::get('transfer/article-by-barcode', [TransferStockController::class, 'apiArticleByBarcode']);
Route::post('transfer/store',             [TransferStockController::class, 'apiStore']);
    Route::get('/count-list',              [StoController::class, 'countList']);
    Route::get('/count/detail',            [StoCountController::class, 'detail']);
    Route::get('/count/articles',          [StoCountController::class, 'articles']);
    Route::get('/count/available-numbers', [StoCountController::class, 'availableNumbers']);
    Route::post('/count/store-line',       [StoCountController::class, 'storeLine']);
    Route::post('/count/store-sheet',      [StoCountController::class, 'storeSheet']);
    Route::put('/count/line/{dtlId}',      [StoCountController::class, 'updateLine']);
    Route::delete('/count/line/{dtlId}',   [StoCountController::class, 'deleteLine']);
    Route::post('/count/finish',           [StoCountController::class, 'finish']);
});