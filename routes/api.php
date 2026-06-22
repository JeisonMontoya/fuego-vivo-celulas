<?php

use App\Http\Controllers\Api\V1\CellController;
use App\Http\Controllers\Api\V1\LeaderController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {

    // Rutas de Líderes
    Route::get('/leaders/search', [LeaderController::class, 'search']);
    Route::get('/leaders/available', [LeaderController::class, 'available']);
    Route::get('/leaders/stats', [LeaderController::class, 'stats']);
    Route::get('/leaders/{id}/validate', [LeaderController::class, 'validateLeader']);
    Route::apiResource('leaders', LeaderController::class)->only(['index', 'show']);

    // Rutas de Células
    Route::apiResource('cells', CellController::class)->only(['show']);

});
