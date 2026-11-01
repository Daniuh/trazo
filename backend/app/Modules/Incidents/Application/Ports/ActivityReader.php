<?php

declare(strict_types=1);

// Lectura del historial de un incidente, de lo más reciente a lo más antiguo y paginado.
// No expone IP ni navegador: eso queda para la auditoría global. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Ports;

interface ActivityReader
{
    /**
     * @return array{items: list<array{type: array{code: string, name: string}, user: array{id: string, name: string}, changes: array<string, mixed>|null, note: string|null, created_at: string}>, total: int, page: int, per_page: int}
     */
    public function forIncident(int $incidentId, int $page, int $perPage): array;
}
