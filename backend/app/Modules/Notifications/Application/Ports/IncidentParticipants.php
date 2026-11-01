<?php

declare(strict_types=1);

// Quiénes están relacionados con un incidente cuando pasa algo. Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Application\Ports;

use App\Modules\Notifications\Domain\Participants;

interface IncidentParticipants
{
    /**
     * Quien creó el incidente, quien lo tiene asignado y quienes escribieron en su conversación.
     */
    public function ofConversation(int $incidentId): ?Participants;

    /**
     * Las personas de un registro del historial: el asignado es el que dejó ese registro (no el de ahora, que pudo
     * cambiar después) y, si es una decisión de reapertura, quien la pidió.
     */
    public function ofActivity(int $activityId): ?Participants;
}
