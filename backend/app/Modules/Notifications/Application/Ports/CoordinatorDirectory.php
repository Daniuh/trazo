<?php

declare(strict_types=1);

// Quiénes reparten el trabajo en este momento, según sus permisos efectivos. Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Application\Ports;

use App\Modules\Notifications\Domain\Coordinators;

interface CoordinatorDirectory
{
    public function current(): Coordinators;
}
