<?php

declare(strict_types=1);

// La regla de visibilidad (VisibilityScope) escrita como condición SQL. La comparten el listado y el dashboard,
// así una cifra del dashboard siempre coincide con lo que la persona encuentra en el listado.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Domain\VisibilityScope;
use App\Modules\Incidents\Infrastructure\Models\Incident;
use Illuminate\Database\Eloquent\Builder;

final class VisibilitySql
{
    /**
     * @param  Builder<Incident>  $query
     */
    public static function apply(Builder $query, int $actorId, VisibilityScope $scope): void
    {
        if ($scope->all) {
            return;
        }
        if ($scope->isEmpty()) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->where(function (Builder $visible) use ($actorId, $scope): void {
            if ($scope->own) {
                $visible->orWhere('incidents.created_by', $actorId);
            }
            if ($scope->assigned) {
                $visible->orWhere('incidents.assigned_to', $actorId);
            }
            if ($scope->unassigned) {
                $visible->orWhereNull('incidents.assigned_to');
            }
        });
    }
}
