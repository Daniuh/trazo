<?php

declare(strict_types=1);

// Configuración transversal de la aplicación. Lo propio de cada módulo vive en su provider.
// Documentado en docs/backend/shared.md

namespace App\Providers;

use App\Modules\Shared\Application\Ports\AdminLog;
use App\Modules\Shared\Application\Ports\Clock;
use App\Modules\Shared\Application\Ports\Transaction;
use App\Modules\Shared\Infrastructure\Console\ResetLimitsCommand;
use App\Modules\Shared\Infrastructure\DatabaseAdminLog;
use App\Modules\Shared\Infrastructure\LaravelSupport;
use App\Modules\Users\Infrastructure\UserPermissions;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> Puertos que comparten todos los módulos */
    public array $bindings = [
        Clock::class => LaravelSupport::class,
        Transaction::class => LaravelSupport::class,
    ];

    public function register(): void
    {
        // Los permisos de cada usuario se consultan una sola vez por petición, aunque varios módulos los pidan
        $this->app->scoped(UserPermissions::class);
        // Los tipos de acción se leen una vez por petición; registran la administración y la exportación
        $this->app->scoped(AdminLog::class, DatabaseAdminLog::class);
    }

    public function boot(): void
    {
        // Herramienta de desarrollo: en producción el comando ni siquiera se registra
        if ($this->app->runningInConsole() && $this->app->environment('local', 'testing')) {
            $this->commands([ResetLimitsCommand::class]);
        }

        // Límite general de la API: por usuario si hay sesión, por IP si no. Configurable solo para pruebas de carga
        // o escaneos locales; en producción se queda en 120
        RateLimiter::for('api', fn (Request $request): Limit => Limit::perMinute((int) config('trazo.api_rate_limit'))->by(
            $request->user()?->getAuthIdentifier() ?? $request->ip(),
        ));
    }
}
