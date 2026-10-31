<?php

declare(strict_types=1);

// La hora actual, inyectada para que las reglas de fechas se puedan probar.
// Documentado en docs/backend/shared.md

namespace App\Modules\Shared\Application\Ports;

use DateTimeImmutable;

interface Clock
{
    public function now(): DateTimeImmutable;
}
