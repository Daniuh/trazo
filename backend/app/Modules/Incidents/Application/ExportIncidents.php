<?php

declare(strict_types=1);

// Caso de uso: exportar los incidentes del listado tal como están filtrados. Exige incidents.export, respeta la misma
// visibilidad del listado, rechaza lo que pase del tope y deja constancia en el registro de administración.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application;

use App\Modules\Incidents\Application\Data\IncidentExport;
use App\Modules\Incidents\Application\Data\IncidentListCriteria;
use App\Modules\Incidents\Application\Ports\IncidentCatalog;
use App\Modules\Incidents\Application\Ports\IncidentExportQuery;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\Exceptions\IncidentExceptions;
use App\Modules\Incidents\Domain\VisibilityScope;
use App\Modules\Shared\Application\AdminActionType;
use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\AdminLog;
use App\Modules\Shared\Domain\Exceptions\ForbiddenException;
use DateTimeImmutable;
use DateTimeInterface;

final readonly class ExportIncidents
{
    public function __construct(
        private IncidentExportQuery $query,
        private IncidentCatalog $catalog,
        private AdminLog $log,
        private int $maxRows,
    ) {}

    public function handle(Actor $actor, IncidentListCriteria $criteria, RequestContext $context): IncidentExport
    {
        if (! $actor->can('incidents.export') || ($criteria->deleted && ! $actor->can('incidents.restore'))) {
            throw new ForbiddenException;
        }

        $scope = VisibilityScope::forList($actor, $criteria->deleted);
        $total = $this->query->count($actor->id, $scope, $criteria);
        if ($total > $this->maxRows) {
            throw IncidentExceptions::exportTooLarge($total, $this->maxRows);
        }

        // Se registra al pedirla: una descarga cortada a mitad igual dejó salir datos
        $this->log->record(AdminActionType::IncidentsExported, $actor->id, null, [
            'rows' => $total,
            'criteria' => $this->criteria($criteria),
        ], $context);

        return new IncidentExport($total, $this->query->rows($actor->id, $scope, $criteria));
    }

    /**
     * Solo los criterios que se usaron, para saber después qué salió. Estados y prioridades con código y nombre
     * (como el historial), personas por su ULID.
     *
     * @return array<string, mixed>
     */
    private function criteria(IncidentListCriteria $criteria): array
    {
        $date = fn (?DateTimeImmutable $value): ?string => $value?->format(DateTimeInterface::ATOM);
        $status = fn (string $code): array => ['code' => $code, 'name' => $this->catalog->status($code)->name ?? $code];
        $priority = fn (string $code): array => ['code' => $code, 'name' => $this->catalog->priority($code)->name ?? $code];

        return array_filter([
            'search' => $criteria->search,
            'status' => array_map($status, $criteria->statuses),
            'priority' => array_map($priority, $criteria->priorities),
            'assignee' => $criteria->assignees,
            'unassigned' => $criteria->unassigned,
            'creator' => $criteria->creator,
            'created_from' => $date($criteria->createdFrom),
            'created_to' => $date($criteria->createdTo),
            'due_from' => $date($criteria->dueFrom),
            'due_to' => $date($criteria->dueTo),
            'overdue' => $criteria->overdue,
            'deleted' => $criteria->deleted,
        ], fn (mixed $value): bool => $value !== null && $value !== [] && $value !== false);
    }
}
