<?php

declare(strict_types=1);

// Base de las validaciones para crear y editar un incidente: título, descripción y prioridad, comunes a las dos.
// Lo propio de cada una (vencimiento, asignado y versión) está en CreateIncidentRequest y UpdateIncidentRequest.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http\Requests;

use App\Modules\Incidents\Application\Data\IncidentInput;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

abstract class IncidentRequest extends FormRequest
{
    // Un título concreto: cabe en una línea del listado, de una tarjeta y de un aviso (ADR-0073)
    public const int TITLE_MIN = 5;

    public const int TITLE_MAX = 80;

    /**
     * Reglas propias de crear o de editar.
     *
     * @return array<string, list<mixed>>
     */
    abstract protected function ownRules(): array;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:'.self::TITLE_MIN, 'max:'.self::TITLE_MAX],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'string', Rule::exists('priorities', 'code')->where('is_active', true)],
            ...$this->ownRules(),
        ];
    }

    public function incidentInput(): IncidentInput
    {
        $dueDate = $this->filled('due_date') ? new DateTimeImmutable($this->string('due_date')->toString()) : null;

        return new IncidentInput(
            $this->string('title')->toString(),
            $this->string('description')->toString(),
            $this->string('priority')->toString(),
            $dueDate?->setTimezone(new DateTimeZone('UTC')),
        );
    }
}
