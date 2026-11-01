<?php

declare(strict_types=1);

// Mensajes de la conversación de cada incidente. Escribir y borrar avisan del cambio (IncidentCommented) cuando la
// transacción se confirma. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Ports;

use App\Modules\Incidents\Application\Data\CommentRecord;
use App\Modules\Incidents\Application\Data\IncidentRecord;

interface IncidentComments
{
    public function add(IncidentRecord $incident, int $authorId, string $body): CommentRecord;

    public function find(int $incidentId, string $ulid): ?CommentRecord;

    // Vacía el texto y anota cuándo se borró; la fila queda para que la conversación conserve su orden
    public function markDeleted(IncidentRecord $incident, CommentRecord $comment): CommentRecord;

    /**
     * Los más recientes primero, de a $limit, empezando antes del mensaje $before (si viene).
     *
     * @return list<CommentRecord>
     */
    public function page(int $incidentId, ?string $before, int $limit): array;

    // Mensajes que siguen en pie (los borrados no cuentan)
    public function count(int $incidentId): int;
}
