<?php

declare(strict_types=1);

// Conecta el módulo de incidentes con Laravel: implementaciones de los puertos.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\DueWindows;
use App\Modules\Incidents\Application\ExportIncidents;
use App\Modules\Incidents\Application\Ports\ActivityLog;
use App\Modules\Incidents\Application\Ports\ActivityReader;
use App\Modules\Incidents\Application\Ports\AssigneeDirectory;
use App\Modules\Incidents\Application\Ports\IncidentCatalog;
use App\Modules\Incidents\Application\Ports\IncidentComments;
use App\Modules\Incidents\Application\Ports\IncidentExportQuery;
use App\Modules\Incidents\Application\Ports\IncidentListQuery;
use App\Modules\Incidents\Application\Ports\IncidentRepository;
use App\Modules\Incidents\Application\Ports\MyIncidentsQuery;
use App\Modules\Incidents\Application\Ports\ReopenRequests;
use App\Modules\Incidents\Http\IncidentCsv;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class IncidentServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        IncidentRepository::class => EloquentIncidentRepository::class,
        AssigneeDirectory::class => EloquentAssigneeDirectory::class,
        ActivityReader::class => EloquentActivityReader::class,
        IncidentListQuery::class => EloquentIncidentList::class,
        ReopenRequests::class => EloquentReopenRequests::class,
        IncidentComments::class => DatabaseIncidentComments::class,
        MyIncidentsQuery::class => EloquentMyIncidents::class,
    ];

    public function register(): void
    {
        // Una sola lectura de catálogos y tipos de actividad por petición
        $this->app->scoped(IncidentCatalog::class, EloquentIncidentCatalog::class);
        $this->app->scoped(ActivityLog::class, EloquentActivityLog::class);
        $this->app->bind(IncidentCodes::class, fn (): IncidentCodes => IncidentCodes::fromConfig());

        $this->app->bind(IncidentExportQuery::class, fn ($app): IncidentExportQuery => new EloquentIncidentExport(
            $app->make(IncidentFilters::class),
            $app->make(IncidentGroups::class),
            $app->make(IncidentCatalog::class),
            (int) config('trazo.export.chunk_size'),
        ));
        $this->app->when(ExportIncidents::class)->needs('$maxRows')
            ->give(fn (): int => (int) config('trazo.export.max_rows'));
        $this->app->when([IncidentCsv::class, DueWindows::class])->needs('$timezone')
            ->give(fn (): string => (string) config('trazo.dashboard.timezone'));
        // Un mismo "ahora" para el orden del listado y el grupo de cada incidente
        $this->app->scoped(DueWindows::class);
    }

    public function boot(): void
    {
        // Exportar: 5 por minuto por persona
        RateLimiter::for('export', fn (Request $request): Limit => Limit::perMinute(5)->by(
            'export|'.($request->user()?->getAuthIdentifier() ?? $request->ip()),
        ));
        // Escribir en una conversación: 20 mensajes por minuto por persona, de sobra para conversar y no para inundar
        RateLimiter::for('comments', fn (Request $request): Limit => Limit::perMinute(20)->by(
            'comments|'.($request->user()?->getAuthIdentifier() ?? $request->ip()),
        ));
    }
}
