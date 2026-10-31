<?php

declare(strict_types=1);

// Validación de entrada para cambiar el estado o la asignación. Ambos exigen la versión que se conocía.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class WorkflowRequest extends FormRequest
{
    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $rules = ['version' => ['required', 'integer', 'min:1']];

        return $this->routeIs('incidents.status')
            ? [...$rules, 'status' => ['required', 'string', 'max:30']]
            // null deja el incidente sin asignar
            : [...$rules, 'assignee' => ['present', 'nullable', 'string', 'ulid']];
    }

    public function version(): int
    {
        return $this->integer('version');
    }

    public function status(): string
    {
        return $this->string('status')->toString();
    }

    public function assignee(): ?string
    {
        return $this->filled('assignee') ? $this->string('assignee')->lower()->toString() : null;
    }
}
