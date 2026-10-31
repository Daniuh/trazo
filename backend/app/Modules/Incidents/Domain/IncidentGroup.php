<?php

declare(strict_types=1);

// Criterios por los que se puede agrupar el listado (lista blanca). Sin grupo, el listado solo sigue el orden elegido.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain;

enum IncidentGroup: string
{
    case Due = 'due';
    case Status = 'status';
    case Priority = 'priority';
    case Assignee = 'assignee';
}
