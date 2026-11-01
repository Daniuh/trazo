<?php

declare(strict_types=1);

// Visibilidad, filtros, búsqueda y orden del listado traducidos a SQL; la exportación usa exactamente lo mismo.
// Los catálogos se filtran por su código estable y las personas por su ULID; nunca llegan ids internos desde fuera.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\Data\IncidentListCriteria;
use App\Modules\Incidents\Application\Ports\IncidentCatalog;
use App\Modules\Incidents\Domain\IncidentSort;
use App\Modules\Incidents\Domain\StatusFlags;
use App\Modules\Incidents\Domain\VisibilityScope;
use App\Modules\Incidents\Infrastructure\Models\Incident;
use Closure;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

final readonly class IncidentFilters
{
    // "INC-123" o "inc-000123": se busca por código exacto
    private const CODE_PATTERN = '/^INC-?(\d{1,10})$/i';

    private const MIN_TERM_LENGTH = 3;

    public function __construct(private IncidentCatalog $catalog) {}

    /**
     * Lo que la persona puede ver y pidió, sin orden ni página. Lo comparten el listado y la exportación.
     *
     * @return Builder<Incident>
     */
    public function query(int $actorId, VisibilityScope $scope, IncidentListCriteria $criteria): Builder
    {
        $query = Incident::query()->select('incidents.*');
        if ($criteria->deleted) {
            $query->onlyTrashed();
        }

        VisibilitySql::apply($query, $actorId, $scope);
        $this->apply($query, $criteria);

        return $query;
    }

    /**
     * Orden pedido y, siempre, el id como desempate: así las páginas no repiten ni saltan filas.
     *
     * @param  Builder<Incident>  $query
     * @return Builder<Incident>
     */
    public function sort(Builder $query, IncidentListCriteria $criteria): Builder
    {
        $direction = $criteria->descending ? 'desc' : 'asc';

        match ($criteria->sort) {
            IncidentSort::Priority => $query->join('priorities', 'priorities.id', '=', 'incidents.priority_id')->orderBy('priorities.level', $direction),
            IncidentSort::Status => $query->join('statuses', 'statuses.id', '=', 'incidents.status_id')->orderBy('statuses.sort_order', $direction),
            IncidentSort::Code => null,
            default => $query->orderBy('incidents.'.$criteria->sort->value, $direction),
        };

        return $query->orderBy('incidents.id', $direction);
    }

    /**
     * @param  Builder<Incident>  $query
     */
    private function apply(Builder $query, IncidentListCriteria $criteria): void
    {
        if ($criteria->statuses !== []) {
            $query->whereIn('incidents.status_id', $this->ids($criteria->statuses, fn (string $code) => $this->catalog->status($code)?->id));
        }
        if ($criteria->priorities !== []) {
            $query->whereIn('incidents.priority_id', $this->ids($criteria->priorities, fn (string $code) => $this->catalog->priority($code)?->id));
        }
        if ($criteria->assignees !== [] || $criteria->unassigned) {
            $query->where(function (Builder $assigned) use ($criteria): void {
                if ($criteria->assignees !== []) {
                    $assigned->orWhereIn('incidents.assigned_to', $this->userIds($criteria->assignees));
                }
                if ($criteria->unassigned) {
                    $assigned->orWhereNull('incidents.assigned_to');
                }
            });
        }
        if ($criteria->creator !== null) {
            $query->whereIn('incidents.created_by', $this->userIds([$criteria->creator]));
        }

        $this->range($query, 'created_at', $criteria->createdFrom, $criteria->createdTo);
        $this->range($query, 'due_date', $criteria->dueFrom, $criteria->dueTo);

        // "Vencido" no se guarda: pendiente y con el vencimiento ya pasado
        if ($criteria->overdue) {
            $query->whereIn('incidents.status_id', $this->pendingStatusIds())->where('incidents.due_date', '<', now());
        }

        if ($criteria->search !== null) {
            $this->search($query, $criteria->search);
        }
    }

    /**
     * @param  Builder<Incident>  $query
     */
    private function search(Builder $query, string $text): void
    {
        if (preg_match(self::CODE_PATTERN, trim($text), $match) === 1) {
            $query->where('incidents.code', sprintf('INC-%06d', (int) $match[1]));

            return;
        }

        // Cada palabra es obligatoria y se busca por prefijo ("impre" encuentra "impresora").
        // Se quitan los operadores del modo booleano para que nadie pueda alterar la consulta.
        $words = preg_split('/[^\p{L}\p{N}]+/u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $terms = array_filter($words, fn (string $word): bool => mb_strlen($word) >= self::MIN_TERM_LENGTH);
        $expression = implode(' ', array_map(fn (string $word): string => "+{$word}*", $terms));

        $query->whereFullText(['incidents.title', 'incidents.description'], $expression, ['mode' => 'boolean']);
    }

    /**
     * @param  Builder<Incident>  $query
     */
    private function range(Builder $query, string $column, ?DateTimeImmutable $from, ?DateTimeImmutable $to): void
    {
        if ($from !== null) {
            $query->where("incidents.{$column}", '>=', $from);
        }
        if ($to !== null) {
            $query->where("incidents.{$column}", '<=', $to);
        }
    }

    /**
     * Un código que no existe no encuentra nada (nunca amplía el resultado).
     *
     * @param  list<string>  $codes
     * @param  callable(string): (int|null)  $resolve
     * @return list<int>
     */
    private function ids(array $codes, callable $resolve): array
    {
        $ids = array_values(array_filter(array_map($resolve, $codes), fn (?int $id): bool => $id !== null));

        return $ids === [] ? [0] : $ids;
    }

    /**
     * @return list<int>
     */
    private function pendingStatusIds(): array
    {
        $pending = array_filter($this->catalog->statuses(), fn (StatusFlags $status): bool => $status->isPending);

        return array_values(array_map(fn (StatusFlags $status): int => $status->id, $pending));
    }

    /**
     * @param  list<string>  $ulids
     */
    private function userIds(array $ulids): Closure
    {
        return fn (QueryBuilder $users) => $users->select('id')->from('users')->whereIn('ulid', $ulids);
    }
}
