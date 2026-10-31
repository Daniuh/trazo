<?php

declare(strict_types=1);

// Un cambio de un incidente en la auditoría: qué, quién, cuándo, sobre cuál incidente y desde dónde. Trae también lo
// necesario para saber si quien mira puede abrir el incidente. Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Application\Data;

use App\Modules\Incidents\Domain\IncidentState;
use DateTimeImmutable;

final readonly class IncidentAuditEntry
{
    /**
     * @param  array<string, mixed>|null  $changes
     */
    public function __construct(
        public int $id,
        public DateTimeImmutable $createdAt,
        public string $typeCode,
        public string $typeName,
        public PersonRef $actor,
        public string $incidentId,
        public string $incidentCode,
        public string $incidentTitle,
        public IncidentState $incident,
        public ?array $changes,
        public ?string $ipAddress,
        public ?string $userAgent,
    ) {}
}
