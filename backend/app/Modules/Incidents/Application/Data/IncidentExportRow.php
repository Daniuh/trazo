<?php

declare(strict_types=1);

// Un incidente tal como sale en la exportación: solo lo que va al archivo, con los nombres ya resueltos.
// La descripción no va: es larga, tiene saltos de línea y no sirve para un reporte. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Data;

use App\Modules\Incidents\Domain\StatusFlags;
use DateTimeImmutable;

final readonly class IncidentExportRow
{
    public function __construct(
        public string $code,
        public string $title,
        public StatusFlags $status,
        public PriorityInfo $priority,
        public string $creator,
        public ?string $assignee,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $dueDate,
        public ?DateTimeImmutable $resolvedAt,
    ) {}
}
