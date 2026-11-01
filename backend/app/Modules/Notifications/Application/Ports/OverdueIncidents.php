<?php

declare(strict_types=1);

// Los incidentes pendientes que vencieron hace poco y aún no tienen el aviso de ese vencimiento.
// Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Application\Ports;

use App\Modules\Notifications\Application\Data\OverdueIncident;
use DateTimeImmutable;

interface OverdueIncidents
{
    /**
     * Vencidos entre $since y $now (sin eliminar y en un estado pendiente) que no tienen un aviso creado después de su
     * vencimiento. Así un mismo vencimiento avisa una sola vez, y uno nuevo (si cambió la fecha) vuelve a avisar.
     *
     * @return list<OverdueIncident>
     */
    public function withoutNotice(DateTimeImmutable $since, DateTimeImmutable $now): array;
}
