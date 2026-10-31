<?php

declare(strict_types=1);

// Rutas de sesión y MFA (/api/v1/auth). Las del grupo "challenge" también aceptan el token de un ingreso a medias;
// el resto exige una sesión completa (ver AuthServiceProvider). Documentado en docs/backend/auth.md

use App\Modules\Auth\Http\AuthController;
use App\Modules\Auth\Http\MfaController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::controller(AuthController::class)->group(function (): void {
        Route::post('login', 'login')->middleware('throttle:login')->name('login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('logout', 'logout')->name('logout');
            Route::get('me', 'me')->name('me');
        });
    });

    Route::prefix('mfa')->name('mfa.')->middleware('auth:sanctum')->controller(MfaController::class)->group(function (): void {
        Route::name('challenge.')->group(function (): void {
            Route::post('verify', 'verify')->middleware('throttle:mfa')->name('verify');
            Route::post('setup', 'start')->name('setup');
            Route::post('setup/confirm', 'confirm')->middleware('throttle:mfa')->name('confirm');
        });

        Route::post('recovery-codes', 'regenerate')->middleware('throttle:mfa')->name('recovery-codes');
        Route::delete('', 'disable')->middleware('throttle:mfa')->name('disable');
    });
});
