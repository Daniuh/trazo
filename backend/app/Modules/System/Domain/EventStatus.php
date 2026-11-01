<?php

declare(strict_types=1);

// Estado de un evento del sistema: abierto (nadie lo miró), revisado (alguien lo está viendo) y resuelto.
// Si un resuelto vuelve a ocurrir, se reabre solo. Documentado en docs/backend/system.md

namespace App\Modules\System\Domain;

enum EventStatus: string
{
    case Open = 'open';
    case Acknowledged = 'acknowledged';
    case Resolved = 'resolved';
}
