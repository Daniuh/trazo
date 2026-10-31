<?php

declare(strict_types=1);

// Caso de uso: listar incidentes. Cada persona ve solo lo que puede ver; los eliminados, solo quien puede restaurarlos.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application;

use App\Modules\Incidents\Application\Data\IncidentListCriteria;
use App\Modules\Incidents\Application\Data\IncidentPage;
use App\Modules\Incidents\Application\Ports\IncidentListQuery;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\VisibilityScope;
use App\Modules\Shared\Domain\Exceptions\ForbiddenException;

final readonly class ListIncidents
{
    public function __construct(private IncidentListQuery $query) {}

    public function handle(Actor $actor, IncidentListCriteria $criteria): IncidentPage
    {
        if ($criteria->deleted && ! $actor->can('incidents.restore')) {
            throw new ForbiddenException;
        }

        // Quien puede restaurar ve todos los eliminados, como en el detalle
        $scope = $criteria->deleted ? new VisibilityScope(true, false, false, false) : VisibilityScope::for($actor);

        return $this->query->search($actor->id, $scope, $criteria);
    }
}
