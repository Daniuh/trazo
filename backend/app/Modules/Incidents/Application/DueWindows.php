<?php

declare(strict_types=1);

// Da los límites de los grupos de vencimiento de la petición en curso. Se calculan una sola vez, para que el orden del
// listado y el grupo de cada incidente usen el mismo "ahora". Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application;

use App\Modules\Incidents\Domain\DueWindow;
use App\Modules\Shared\Application\Ports\Clock;
use DateTimeZone;

final class DueWindows
{
    private ?DueWindow $current = null;

    public function __construct(
        private readonly Clock $clock,
        private readonly string $timezone,
    ) {}

    public function current(): DueWindow
    {
        return $this->current ??= DueWindow::at($this->clock->now(), new DateTimeZone($this->timezone));
    }
}
