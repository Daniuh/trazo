<?php

declare(strict_types=1);

// Rutas de la auditoría global (/api/v1/audit). Todas exigen sesión; cada pestaña revisa su propio permiso.
// Documentado en docs/backend/audit.md

use App\Modules\Audit\Http\AuditController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('audit')->name('audit.')->controller(AuditController::class)->group(function (): void {
    Route::get('catalog', 'catalog')->name('catalog');
    Route::get('incidents', 'incidents')->name('incidents');
    Route::get('admin', 'admin')->name('admin');
});
