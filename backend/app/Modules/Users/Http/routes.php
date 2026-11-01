<?php

declare(strict_types=1);

// Rutas de la administración de usuarios y roles (/api/v1). Todas exigen sesión; los permisos y la jerarquía los
// revisan los casos de uso. Documentado en docs/backend/users.md

use App\Modules\Shared\Http\Middleware\EnsureIdempotency;
use App\Modules\Users\Http\PermissionController;
use App\Modules\Users\Http\RoleController;
use App\Modules\Users\Http\Rules\RoleCode;
use App\Modules\Users\Http\UserAccessController;
use App\Modules\Users\Http\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function (): void {
    Route::get('permissions', [PermissionController::class, 'index']);

    Route::prefix('roles')->name('roles.')->controller(RoleController::class)->group(function (): void {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->middleware(EnsureIdempotency::class)->name('store');

        Route::prefix('{role}')->where(['role' => RoleCode::PATTERN])->group(function (): void {
            Route::get('/', 'show')->name('show');
            Route::patch('/', 'update')->name('update');
            Route::delete('/', 'destroy')->name('destroy');
        });
    });

    Route::prefix('users')->name('users.')->group(function (): void {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->middleware(EnsureIdempotency::class)->name('store');

        Route::prefix('{user}')->whereUlid('user')->group(function (): void {
            Route::get('/', [UserController::class, 'show'])->name('show');
            Route::patch('/', [UserController::class, 'update'])->name('update');

            Route::controller(UserAccessController::class)->group(function (): void {
                Route::put('roles', 'role')->name('role');
                Route::put('permissions', 'permissions')->name('permissions');
                Route::post('activate', 'activate')->name('activate');
                Route::post('deactivate', 'deactivate')->name('deactivate');
                Route::delete('mfa', 'resetMfa')->name('mfa');
                Route::post('access-reset', 'resetAccess')->name('access-reset');
            });
        });
    });
});
