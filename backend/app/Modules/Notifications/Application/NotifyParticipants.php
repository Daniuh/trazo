<?php

declare(strict_types=1);

// Caso de uso: crear las notificaciones de un mensaje nuevo o de un registro del historial y avisar a cada
// destinatario en tiempo real. Corre en la cola, después de que el cambio se confirmó. Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Application;

use App\Modules\Incidents\Application\Events\IncidentActivityRecorded;
use App\Modules\Incidents\Application\Events\IncidentCommented;
use App\Modules\Notifications\Application\Ports\CoordinatorDirectory;
use App\Modules\Notifications\Application\Ports\IncidentParticipants;
use App\Modules\Notifications\Application\Ports\NotificationSignals;
use App\Modules\Notifications\Application\Ports\NotificationStore;
use App\Modules\Notifications\Domain\Recipients;

final readonly class NotifyParticipants
{
    public function __construct(
        private IncidentParticipants $participants,
        private NotificationStore $store,
        private NotificationSignals $signals,
        private CoordinatorDirectory $coordinators,
    ) {}

    public function comment(IncidentCommented $event): void
    {
        // Borrar un mensaje no es una noticia: el aviso que ya existía lo mostrará como eliminado
        $people = $event->removed ? null : $this->participants->ofConversation($event->incidentId);
        $users = $people === null ? [] : Recipients::forComment($event->authorId, $people);
        if ($users === []) {
            return;
        }

        $this->store->addComment($users, $event->incidentId, $event->commentId);
        $this->signals->changed($users);
    }

    public function activity(IncidentActivityRecorded $event): void
    {
        $people = $this->participants->ofActivity($event->activityId);
        // Quienes coordinan solo se buscan para los registros que les avisan
        $coordinators = in_array($event->type, Recipients::COORDINATION_ACTIVITIES, true) ? $this->coordinators->current() : null;
        $users = $people === null ? [] : Recipients::forActivity($event->type, $event->actorId, $people, $coordinators);
        if ($users === []) {
            return;
        }

        $this->store->addActivity($users, $event->incidentId, $event->activityId);
        $this->signals->changed(array_keys($users));
    }
}
