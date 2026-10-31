<?php

declare(strict_types=1);

// Conecta el módulo de sistema con Laravel: implementaciones de los puertos (incluido el registro compartido de
// eventos), el oyente de jobs fallidos, el límite de las acciones y los comandos del scheduler.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Infrastructure;

use App\Modules\Shared\Application\Ports\SystemEvents;
use App\Modules\System\Application\CheckSystem;
use App\Modules\System\Application\Ports\FailedJobs;
use App\Modules\System\Application\Ports\HealthProbe;
use App\Modules\System\Application\Ports\Heartbeats;
use App\Modules\System\Application\Ports\IdentityCheck;
use App\Modules\System\Application\Ports\MaintenanceSwitch;
use App\Modules\System\Application\Ports\SystemControls;
use App\Modules\System\Application\Ports\SystemEventLog;
use App\Modules\System\Infrastructure\Console\CheckSystemCommand;
use App\Modules\System\Infrastructure\Console\PruneSystemEventsCommand;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

final class SystemServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        SystemEventLog::class => DatabaseEventLog::class,
        Heartbeats::class => CacheHeartbeats::class,
        MaintenanceSwitch::class => CacheMaintenanceSwitch::class,
        SystemControls::class => LaravelSystemControls::class,
        FailedJobs::class => DatabaseFailedJobs::class,
        IdentityCheck::class => UsersIdentityCheck::class,
    ];

    public function register(): void
    {
        // El catálogo de tipos se lee una vez por petición
        $this->app->scoped(SystemEvents::class, DatabaseSystemEvents::class);

        $this->app->bind(HealthProbe::class, fn ($app): HealthProbe => new LaravelHealthProbe(
            $app->make(Heartbeats::class),
            (string) config('broadcasting.connections.reverb.options.host'),
            (int) config('broadcasting.connections.reverb.options.port'),
        ));
        $this->app->when(CheckSystem::class)->needs('$retentionDays')
            ->give(fn (): int => (int) config('trazo.system.events_retention_days'));
    }

    public function boot(): void
    {
        Event::listen(JobFailed::class, fn (JobFailed $event) => $this->app->make(FailureEvents::class)->jobFailed($event));

        // Acciones de sistema: 5 por minuto por persona
        RateLimiter::for('system-actions', fn (Request $request): Limit => Limit::perMinute(5)->by(
            'system|'.($request->user()?->getAuthIdentifier() ?? $request->ip()),
        ));

        if ($this->app->runningInConsole()) {
            $this->commands([CheckSystemCommand::class, PruneSystemEventsCommand::class]);
        }
    }
}
