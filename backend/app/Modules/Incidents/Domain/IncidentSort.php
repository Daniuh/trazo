<?php

declare(strict_types=1);

// Campos por los que se puede ordenar el listado (lista blanca). Cualquier otro valor se rechaza.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain;

enum IncidentSort: string
{
    case CreatedAt = 'created_at';
    case DueDate = 'due_date';
    case UpdatedAt = 'updated_at';
    case Priority = 'priority';
    case Status = 'status';
    case Code = 'code';
}
