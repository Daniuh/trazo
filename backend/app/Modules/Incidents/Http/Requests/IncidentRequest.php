<?php

declare(strict_types=1);

// Validación de entrada para crear y editar un incidente (forma, tamaño y referencias).
// Al editar, el vencimiento solo se envía si cambia. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http\Requests;

use App\Modules\Incidents\Application\Data\IncidentInput;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class IncidentRequest extends FormRequest
{
    // Un título concreto: cabe en una línea del listado, de una tarjeta y de un aviso (ADR-0073)
    public const int TITLE_MIN = 5;

    public const int TITLE_MAX = 80;

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $creating = $this->isMethod('POST');

        return [
            'title' => ['required', 'string', 'min:'.self::TITLE_MIN, 'max:'.self::TITLE_MAX],
            'description' => ['required', 'string', 'max:5000'],
            'priority' => ['required', 'string', Rule::exists('priorities', 'code')->where('is_active', true)],
            // Un vencimiento nuevo no puede quedar en el pasado
            'due_date' => [$creating ? 'nullable' : 'sometimes', 'date', 'after:now'],
            'assignee' => $creating ? ['nullable', 'string', 'ulid'] : ['prohibited'],
            'version' => $creating ? ['prohibited'] : ['required', 'integer', 'min:1'],
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

    public function assignee(): ?string
    {
        return $this->filled('assignee') ? $this->string('assignee')->lower()->toString() : null;
    }

    public function version(): int
    {
        return $this->integer('version');
    }
}
