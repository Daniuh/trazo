<?php

declare(strict_types=1);

// Lectura de la exportación: los mismos incidentes y el mismo orden del listado, sin página, entregados por partes
// para no cargar todo en memoria. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Ports;

use App\Modules\Incidents\Application\Data\IncidentExportRow;
use App\Modules\Incidents\Application\Data\IncidentListCriteria;
use App\Modules\Incidents\Domain\VisibilityScope;

interface IncidentExportQuery
{
    public function count(int $actorId, VisibilityScope $scope, IncidentListCriteria $criteria): int;

    /**
     * @return iterable<IncidentExportRow>
     */
    public function rows(int $actorId, VisibilityScope $scope, IncidentListCriteria $criteria): iterable;
}
