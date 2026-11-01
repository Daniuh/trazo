<?php

declare(strict_types=1);

// Qué le pasó a un incidente, tal como viaja en el aviso de tiempo real. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Events;

enum IncidentChange: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
}
