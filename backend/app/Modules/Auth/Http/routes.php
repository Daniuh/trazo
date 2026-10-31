<?php

declare(strict_types=1);

// Rutas de sesión (/api/v1/auth). Documentado en docs/backend/auth.md

use App\Modules\Auth\Http\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(AuthController::class)->group(function (): void {
    Route::post('login', 'login')->middleware('throttle:login');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('logout', 'logout');
        Route::get('me', 'me');
    });
});
