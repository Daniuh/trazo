<?php

declare(strict_types=1);

// Consultas de lectura del dashboard (CQRS ligero): agregados en SQL con la misma visibilidad del listado.
// Devuelven ids de catálogo; los nombres los pone la aplicación. Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application\Ports;

use App\Modules\Dashboard\Application\Data\IncidentDigest;
use App\Modules\Dashboard\Application\Data\WorkloadRow;
use App\Modules\Incidents\Domain\VisibilityScope;
use DateTimeImmutable;

interface DashboardQueries
{
    /**
     * @return array<int, array<int, int>> Cantidad por estado y prioridad: [status_id][priority_id]
     */
    public function countsByStatusAndPriority(int $actorId, VisibilityScope $scope): array;

    /**
     * @param  list<int>  $pendingStatusIds
     */
    public function overdueCount(int $actorId, VisibilityScope $scope, array $pendingStatusIds, DateTimeImmutable $now): int;

    /**
     * @param  list<int>  $pendingStatusIds
     */
    public function unassignedCount(int $actorId, VisibilityScope $scope, array $pendingStatusIds): int;

    /**
     * @param  'created_at'|'resolved_at'  $column
     * @return array<string, int> Cantidad por hora UTC ("2026-10-22 14:00:00")
     */
    public function countByHour(int $actorId, VisibilityScope $scope, string $column, DateTimeImmutable $from): array;

    /**
     * Pendientes que vencen entre dos fechas, del más próximo al más lejano.
     *
     * @param  list<int>  $pendingStatusIds
     */
    public function dueBetween(int $actorId, VisibilityScope $scope, array $pendingStatusIds, DateTimeImmutable $from, DateTimeImmutable $to, int $limit): IncidentDigest;

    /**
     * Pendientes asignados a la persona, del que vence antes al que vence después.
     *
     * @param  list<int>  $pendingStatusIds
     */
    public function assignedTo(int $actorId, VisibilityScope $scope, array $pendingStatusIds, int $limit): IncidentDigest;

    /**
     * Pendientes y vencidos de cada persona asignada, de la más cargada a la menos.
     *
     * @param  list<int>  $pendingStatusIds
     * @return list<WorkloadRow>
     */
    public function workload(int $actorId, VisibilityScope $scope, array $pendingStatusIds, DateTimeImmutable $now, int $limit): array;

    public function userUlid(int $userId): string;
}
