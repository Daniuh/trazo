<?php

declare(strict_types=1);

// Persistencia de incidentes. Las escrituras usan bloqueo optimista: si la versión ya cambió, no se guarda nada.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Ports;

use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Shared\Domain\Exceptions\ConflictException;
use DateTimeImmutable;

interface IncidentRepository
{
    public function find(string $ulid, bool $withDeleted = false): ?IncidentRecord;

    /**
     * Crea el incidente con su código legible (TZ-26-0142), tomado del contador del año en la misma transacción.
     */
    public function create(
        string $title,
        string $description,
        int $priorityId,
        int $statusId,
        int $createdBy,
        ?int $assignedTo,
        DateTimeImmutable $dueDate,
    ): string;

    /**
     * Guarda las columnas cambiadas y sube la versión.
     *
     * @param  array<string, scalar|DateTimeImmutable|null>  $columns
     *
     * @throws ConflictException si la versión ya no es la esperada
     */
    public function update(IncidentRecord $incident, int $expectedVersion, array $columns): void;

    public function delete(IncidentRecord $incident): void;

    public function restore(IncidentRecord $incident): void;

    /**
     * Avisa que algo del incidente cambió sin tocar sus columnas (por ejemplo, una solicitud de reapertura).
     */
    public function announce(IncidentRecord $incident): void;
}
