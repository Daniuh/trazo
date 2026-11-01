<?php

declare(strict_types=1);

// Conecta el módulo de notificaciones con Laravel: implementaciones de los puertos, los oyentes de los mensajes y del
// historial de los incidentes, los días que se guardan, la limpieza diaria y el aviso de vencidos.
// Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Infrastructure;

use App\Modules\Incidents\Application\Events\IncidentActivityRecorded;
use App\Modules\Incidents\Application\Events\IncidentCommented;
use App\Modules\Notifications\Application\PersonalNotifications;
use App\Modules\Notifications\Application\Ports\CoordinatorDirectory;
use App\Modules\Notifications\Application\Ports\IncidentParticipants;
use App\Modules\Notifications\Application\Ports\NotificationStore;
use App\Modules\Notifications\Application\Ports\OverdueIncidents;
use App\Modules\Notifications\Infrastructure\Console\NotifyOverdueCommand;
use App\Modules\Notifications\Infrastructure\Console\PruneNotificationsCommand;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class NotificationsServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        NotificationStore::class => DatabaseNotificationStore::class,
        IncidentParticipants::class => DatabaseIncidentParticipants::class,
        CoordinatorDirectory::class => DatabaseCoordinatorDirectory::class,
        OverdueIncidents::class => DatabaseOverdueIncidents::class,
    ];

    public function register(): void
    {
        $this->app->when(PersonalNotifications::class)->needs('$retentionDays')
            ->give(fn (): int => (int) config('trazo.notifications.retention_days'));
    }

    public function boot(): void
    {
        Event::listen(IncidentCommented::class, [CreateNotifications::class, 'comment']);
        Event::listen(IncidentActivityRecorded::class, [CreateNotifications::class, 'activity']);

        if ($this->app->runningInConsole()) {
            $this->commands([PruneNotificationsCommand::class, NotifyOverdueCommand::class]);
        }
    }
}
