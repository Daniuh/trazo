<?php

declare(strict_types=1);

// Un incidente completo, leído con sus catálogos y personas. Lo usan los casos de uso y la respuesta del detalle.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Data;

use App\Modules\Incidents\Domain\IncidentState;
use App\Modules\Incidents\Domain\StatusFlags;
use DateTimeImmutable;

final readonly class IncidentRecord
{
    public function __construct(
        public int $id,
        public string $ulid,
        public string $code,
        public string $title,
        public string $description,
        public PriorityInfo $priority,
        public StatusFlags $status,
        public Person $creator,
        public ?Person $assignee,
        public DateTimeImmutable $dueDate,
        public ?DateTimeImmutable $resolvedAt,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public ?DateTimeImmutable $deletedAt,
        public int $version,
    ) {}

    public function state(): IncidentState
    {
        return new IncidentState(
            $this->creator->id,
            $this->assignee?->id,
            $this->status->isInitial,
            $this->status->isFinal,
            $this->deletedAt !== null,
        );
    }
}
