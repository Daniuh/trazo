<?php

declare(strict_types=1);

// Validación para editar un incidente: lo común (IncidentRequest), el vencimiento solo si cambia (y no en el pasado) y
// la versión que se conocía. El asignado no se cambia aquí: tiene su propio endpoint. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http\Requests;

final class UpdateIncidentRequest extends IncidentRequest
{
    use ReadsVersion;

    protected function ownRules(): array
    {
        return [
            'due_date' => ['sometimes', 'date', 'after:now'],
            // No se envía: la asignación tiene su propio endpoint
            'assignee' => ['prohibited'],
            'version' => ['required', 'integer', 'min:1'],
        ];
    }
}
