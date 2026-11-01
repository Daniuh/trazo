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

        return $this->query->search($actor->id, VisibilityScope::forList($actor, $criteria->deleted), $criteria);
    }
}
