<?php

declare(strict_types=1);

// Datos editables de un incidente, ya validados en forma. Sirve para crear y para editar.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Data;

use DateTimeImmutable;

final readonly class IncidentInput
{
    public function __construct(
        public string $title,
        public string $description,
        public string $priorityCode,
        public ?DateTimeImmutable $dueDate,
    ) {}
}
