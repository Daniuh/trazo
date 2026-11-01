<?php

declare(strict_types=1);

// Oyente de los mensajes y del historial de los incidentes. Va por la cola: crear avisos nunca demora ni hace fallar
// el cambio que los originó. Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Infrastructure;

use App\Modules\Incidents\Application\Events\IncidentActivityRecorded;
use App\Modules\Incidents\Application\Events\IncidentCommented;
use App\Modules\Notifications\Application\NotifyParticipants;
use Illuminate\Contracts\Queue\ShouldQueue;

final readonly class CreateNotifications implements ShouldQueue
{
    public function __construct(private NotifyParticipants $notify) {}

    public function comment(IncidentCommented $event): void
    {
        $this->notify->comment($event);
    }

    public function activity(IncidentActivityRecorded $event): void
    {
        $this->notify->activity($event);
    }
}
