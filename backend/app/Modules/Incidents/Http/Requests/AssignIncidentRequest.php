<?php

declare(strict_types=1);

// Validación para asignar o reasignar: la persona (su ULID, o null para dejarlo sin asignar) y la versión que se
// conocía. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class AssignIncidentRequest extends FormRequest
{
    use ReadsVersion;

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'version' => ['required', 'integer', 'min:1'],
            // null deja el incidente sin asignar
            'assignee' => ['present', 'nullable', 'string', 'ulid'],
        ];
    }

    public function assignee(): ?string
    {
        return $this->filled('assignee') ? $this->string('assignee')->lower()->toString() : null;
    }
}
