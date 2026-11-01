<?php

declare(strict_types=1);

// Rutas del centro de notificaciones (/api/v1/notifications). Todas exigen sesión; cada persona solo toca las suyas.
// Documentado en docs/backend/notifications.md

use App\Modules\Notifications\Http\NotificationController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('notifications')->controller(NotificationController::class)->group(function (): void {
    Route::get('/', 'index');
    Route::get('unread', 'unread');
    Route::post('read', 'readAll');
    Route::post('{notification}/read', 'read')->whereUlid('notification');
});
