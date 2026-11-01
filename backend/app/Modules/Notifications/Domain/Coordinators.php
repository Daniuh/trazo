<?php

declare(strict_types=1);

// Quienes reparten el trabajo, por permiso y no por rol: los que pueden asignar incidentes y los que pueden aprobar una
// reapertura. Solo cuentas activas. Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Domain;

final readonly class Coordinators
{
    /**
     * @param  list<int>  $assigners  con incidents.assign
     * @param  list<int>  $reopenApprovers  con incidents.reopen.approve
     */
    public function __construct(
        public array $assigners = [],
        public array $reopenApprovers = [],
    ) {}
}
