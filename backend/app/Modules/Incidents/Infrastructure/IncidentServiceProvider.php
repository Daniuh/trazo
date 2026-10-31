<?php

declare(strict_types=1);

// Conecta el módulo de incidentes con Laravel: implementaciones de los puertos.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\Ports\ActivityLog;
use App\Modules\Incidents\Application\Ports\ActivityReader;
use App\Modules\Incidents\Application\Ports\AssigneeDirectory;
use App\Modules\Incidents\Application\Ports\Clock;
use App\Modules\Incidents\Application\Ports\IncidentCatalog;
use App\Modules\Incidents\Application\Ports\IncidentListQuery;
use App\Modules\Incidents\Application\Ports\IncidentRepository;
use App\Modules\Incidents\Application\Ports\Transaction;
use Illuminate\Support\ServiceProvider;

final class IncidentServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        IncidentRepository::class => EloquentIncidentRepository::class,
        AssigneeDirectory::class => EloquentAssigneeDirectory::class,
        ActivityReader::class => EloquentActivityReader::class,
        IncidentListQuery::class => EloquentIncidentList::class,
        Clock::class => LaravelSupport::class,
        Transaction::class => LaravelSupport::class,
    ];

    public function register(): void
    {
        // Una sola lectura de catálogos y tipos de actividad por petición
        $this->app->scoped(IncidentCatalog::class, EloquentIncidentCatalog::class);
        $this->app->scoped(ActivityLog::class, EloquentActivityLog::class);
    }
}
