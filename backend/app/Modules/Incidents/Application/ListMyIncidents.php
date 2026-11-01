<?php

declare(strict_types=1);

// Caso de uso: "Mis incidentes", los que la persona tiene asignados, para el Kanban, la lista, el calendario y "Mi día".
// Trae los que siguen en curso y los cerrados o cancelados de los últimos días; con más de lo que cabe en el tablero,
// avisa que quedaron fuera (truncated). Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application;

use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\Ports\MyIncidentsQuery;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Shared\Application\Ports\Clock;
use App\Modules\Shared\Domain\Exceptions\ForbiddenException;

final readonly class ListMyIncidents
{
    // Los terminados se ven unos días, para que lo cerrado no desaparezca del tablero al instante
    public const FINISHED_DAYS = 7;

    public const LIMIT = 500;

    public function __construct(
        private MyIncidentsQuery $query,
        private Clock $clock,
    ) {}

    /**
     * @return array{items: list<IncidentRecord>, truncated: bool}
     */
    public function handle(Actor $actor): array
    {
        // Solo quien puede tener incidentes asignados tiene este tablero
        if (! $actor->can('incidents.view.assigned')) {
            throw new ForbiddenException;
        }

        $since = $this->clock->now()->modify('-'.self::FINISHED_DAYS.' days');
        $items = $this->query->assignedTo($actor->id, $since, self::LIMIT + 1);

        return ['items' => array_slice($items, 0, self::LIMIT), 'truncated' => count($items) > self::LIMIT];
    }
}
