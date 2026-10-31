<?php

declare(strict_types=1);

// Configuración transversal de la aplicación. Lo propio de cada módulo vive en su provider.
// Documentado en docs/backend/shared.md

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Límite general de la API: por usuario si hay sesión, por IP si no
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute(120)->by(
            $request->user()?->getAuthIdentifier() ?? $request->ip(),
        ));
    }
}
