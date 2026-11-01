<?php

declare(strict_types=1);

// Validación de entrada para cambiar el estado (con su nota) o la asignación. Ambos exigen la versión que se conocía.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class WorkflowRequest extends FormRequest
{
    public const NOTE_MAX = 1000;

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        $rules = ['version' => ['required', 'integer', 'min:1']];

        return $this->routeIs('incidents.status')
            ? [...$rules, 'status' => ['required', 'string', 'max:30'], 'note' => ['nullable', 'string', 'max:'.self::NOTE_MAX]]
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

    // Una nota con solo espacios cuenta como vacía
    public function note(): ?string
    {
        $note = $this->string('note')->trim()->toString();

        return $note === '' ? null : $note;
    }

    public function assignee(): ?string
    {
        return $this->filled('assignee') ? $this->string('assignee')->lower()->toString() : null;
    }
}
