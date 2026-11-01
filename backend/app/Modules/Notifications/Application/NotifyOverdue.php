<?php

declare(strict_types=1);

// Caso de uso: avisar de los incidentes que acaban de vencer sin resolverse, a quien los atiende y a quienes reparten
// el trabajo. Lo corre la tarea programada cada 5 minutos; cada vencimiento avisa una sola vez (ADR-0085).
// Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Application;

use App\Modules\Notifications\Application\Ports\CoordinatorDirectory;
use App\Modules\Notifications\Application\Ports\NotificationSignals;
use App\Modules\Notifications\Application\Ports\NotificationStore;
use App\Modules\Notifications\Application\Ports\OverdueIncidents;
use App\Modules\Notifications\Domain\NotificationType;
use App\Modules\Notifications\Domain\Recipients;
use App\Modules\Shared\Application\Ports\Clock;

final readonly class NotifyOverdue
{
    // Solo lo que venció en el último día: si la tarea estuvo detenida más tiempo, no se avisa de golpe lo viejo
    private const LOOKBACK = '-24 hours';

    public function __construct(
        private OverdueIncidents $overdue,
        private CoordinatorDirectory $coordinators,
        private NotificationStore $store,
        private NotificationSignals $signals,
        private Clock $clock,
    ) {}

    /**
     * Devuelve cuántos incidentes avisó.
     */
    public function handle(): int
    {
        $now = $this->clock->now();
        $incidents = $this->overdue->withoutNotice($now->modify(self::LOOKBACK), $now);
        if ($incidents === []) {
            return 0;
        }

        $coordinators = $this->coordinators->current();
        $notified = [];
        foreach ($incidents as $incident) {
            $users = Recipients::forOverdue($incident->assignee, $coordinators);
            if ($users === []) {
                continue;
            }
            $this->store->addNotice($users, $incident->id, NotificationType::IncidentOverdue);
            $notified = [...$notified, ...$users];
        }

        if ($notified !== []) {
            $this->signals->changed(array_values(array_unique($notified)));
        }

        return count($incidents);
    }
}
