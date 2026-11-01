<?php

declare(strict_types=1);

// Validación para crear un incidente: lo común (IncidentRequest), un vencimiento opcional que no quede en el pasado
// (vacío: lo calcula la prioridad) y, si se indica, a quién se asigna. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http\Requests;

final class CreateIncidentRequest extends IncidentRequest
{
    protected function ownRules(): array
    {
        return [
            'due_date' => ['nullable', 'date', 'after:now'],
            'assignee' => ['nullable', 'string', 'ulid'],
            // No se envía: un incidente nuevo no tiene versión anterior
            'version' => ['prohibited'],
        ];
    }

    public function assignee(): ?string
    {
        return $this->filled('assignee') ? $this->string('assignee')->lower()->toString() : null;
    }
}
