<?php

declare(strict_types=1);

// Rutas del tiempo real (/api/v1). Exigen sesión completa: el token a medio MFA no sirve aquí.
// Documentado en docs/backend/realtime.md

use App\Modules\Realtime\Http\RealtimeController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->name('realtime.')->controller(RealtimeController::class)->group(function (): void {
    Route::get('realtime', 'config')->name('config');
    Route::post('broadcasting/auth', 'authorize')->name('authorize');
});
