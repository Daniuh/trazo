<?php

declare(strict_types=1);

// Modo mantenimiento compartido por todos los procesos (en Redis, no en un archivo de cada contenedor).
// Documentado en docs/backend/system.md

namespace App\Modules\System\Application\Ports;

use App\Modules\System\Application\Data\MaintenanceState;

interface MaintenanceSwitch
{
    public function current(): ?MaintenanceState;

    public function enable(int $byUserId): void;

    public function disable(): void;
}
