<?php

declare(strict_types=1);

// Lectura optimizada del listado (CQRS ligero): filtra, ordena y pagina en la base de datos,
// siempre dentro de lo que la persona puede ver. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Ports;

use App\Modules\Incidents\Application\Data\IncidentListCriteria;
use App\Modules\Incidents\Application\Data\IncidentPage;
use App\Modules\Incidents\Domain\VisibilityScope;

interface IncidentListQuery
{
    public function search(int $actorId, VisibilityScope $scope, IncidentListCriteria $criteria): IncidentPage;
}
