<?php

declare(strict_types=1);

// La hora actual, inyectada para que las reglas de fechas se puedan probar.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Ports;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
