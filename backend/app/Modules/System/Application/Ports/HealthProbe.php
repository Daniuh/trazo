<?php

declare(strict_types=1);

// Mide el estado real de cada pieza (base de datos, Redis, cola, señales de vida, tiempo real y disco). Nunca lanza:
// lo que no responde queda como null o false. Documentado en docs/backend/system.md

namespace App\Modules\System\Application\Ports;

use App\Modules\System\Application\Data\HealthMeasurements;

interface HealthProbe
{
    public function measure(): HealthMeasurements;
}
