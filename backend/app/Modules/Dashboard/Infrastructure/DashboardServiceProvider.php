<?php

declare(strict_types=1);

// Conecta el módulo del dashboard con Laravel: puertos, ajustes desde config/trazo.php y la invalidación de la caché.
// Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Infrastructure;

use App\Modules\Dashboard\Application\Data\DashboardSettings;
use App\Modules\Dashboard\Application\Ports\DashboardQueries;
use App\Modules\Dashboard\Application\Ports\LayoutStore;
use App\Modules\Dashboard\Application\Ports\MetricsCache;
use App\Modules\Dashboard\Application\Ports\WidgetCatalog;
use App\Modules\Incidents\Application\Events\IncidentChanged;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class DashboardServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        DashboardQueries::class => EloquentDashboardQueries::class,
        LayoutStore::class => DatabaseLayoutStore::class,
    ];

    public function register(): void
    {
        // Una sola lectura del catálogo por petición
        $this->app->scoped(WidgetCatalog::class, DatabaseWidgetCatalog::class);

        $this->app->singleton(DashboardSettings::class, fn (): DashboardSettings => new DashboardSettings(
            (string) config('trazo.dashboard.timezone'),
            (int) config('trazo.dashboard.list_limit'),
            (int) config('trazo.dashboard.due_soon_hours'),
            (int) config('trazo.dashboard.workload_limit'),
        ));

        $this->app->bind(MetricsCache::class, fn (): MetricsCache => new CacheMetricsCache(
            $this->app->make(Repository::class),
            (int) config('trazo.dashboard.cache_seconds'),
        ));
    }

    public function boot(): void
    {
        Event::listen(IncidentChanged::class, InvalidateDashboardMetrics::class);
    }
}
