<?php

declare(strict_types=1);

// Exportación en SQL: la misma consulta y el mismo orden del listado (IncidentFilters), leída en bloques con solo las
// columnas del archivo. Así la memoria no crece con el número de filas. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\Data\IncidentExportRow;
use App\Modules\Incidents\Application\Data\IncidentListCriteria;
use App\Modules\Incidents\Application\Data\PriorityInfo;
use App\Modules\Incidents\Application\Ports\IncidentCatalog;
use App\Modules\Incidents\Application\Ports\IncidentExportQuery;
use App\Modules\Incidents\Domain\StatusFlags;
use App\Modules\Incidents\Domain\VisibilityScope;
use DateTimeImmutable;
use DateTimeZone;
use stdClass;

final readonly class EloquentIncidentExport implements IncidentExportQuery
{
    public function __construct(
        private IncidentFilters $filters,
        private IncidentCatalog $catalog,
        private int $chunkSize,
    ) {}

    public function count(int $actorId, VisibilityScope $scope, IncidentListCriteria $criteria): int
    {
        return $this->filters->query($actorId, $scope, $criteria)->count();
    }

    public function rows(int $actorId, VisibilityScope $scope, IncidentListCriteria $criteria): iterable
    {
        $query = $this->filters->sort($this->filters->query($actorId, $scope, $criteria), $criteria)
            ->toBase()
            ->join('users as creators', 'creators.id', '=', 'incidents.created_by')
            ->leftJoin('users as assignees', 'assignees.id', '=', 'incidents.assigned_to')
            ->select([
                'incidents.code', 'incidents.title', 'incidents.status_id', 'incidents.priority_id',
                'incidents.created_at', 'incidents.due_date', 'incidents.resolved_at',
                'creators.name as creator', 'assignees.name as assignee',
            ]);

        $statuses = $this->byId($this->catalog->statuses());
        $priorities = $this->byId($this->catalog->priorities());

        /** @var stdClass $row */
        foreach ($query->lazy($this->chunkSize) as $row) {
            yield new IncidentExportRow(
                (string) $row->code,
                (string) $row->title,
                $statuses[(int) $row->status_id],
                $priorities[(int) $row->priority_id],
                (string) $row->creator,
                $row->assignee === null ? null : (string) $row->assignee,
                $this->utc((string) $row->created_at),
                $this->utc((string) $row->due_date),
                $row->resolved_at === null ? null : $this->utc((string) $row->resolved_at),
            );
        }
    }

    /**
     * @template T of StatusFlags|PriorityInfo
     *
     * @param  array<string, T>  $items
     * @return array<int, T>
     */
    private function byId(array $items): array
    {
        $byId = [];
        foreach ($items as $item) {
            $byId[$item->id] = $item;
        }

        return $byId;
    }

    // La base guarda en UTC
    private function utc(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
