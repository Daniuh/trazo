<?php

declare(strict_types=1);

// Rutas del dashboard (/api/v1/dashboard). Todas exigen sesión. Documentado en docs/backend/dashboard.md

use App\Modules\Dashboard\Http\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('dashboard')->group(function (): void {
    Route::get('/', [DashboardController::class, 'show']);
    Route::get('metrics', [DashboardController::class, 'metrics']);
    Route::put('layout', [DashboardController::class, 'saveLayout']);
    Route::delete('layout', [DashboardController::class, 'resetLayout']);
});
