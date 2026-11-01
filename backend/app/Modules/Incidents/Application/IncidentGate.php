<?php

declare(strict_types=1);

// Carga un incidente solo si la persona puede hacer la acción pedida; si no, lanza el error que corresponde.
// Lo usan todos los casos de uso, así nadie se salta las reglas. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application;

use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\Ports\IncidentRepository;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\Denial;
use App\Modules\Incidents\Domain\Exceptions\IncidentExceptions;
use App\Modules\Incidents\Domain\IncidentAccess;
use App\Modules\Incidents\Domain\IncidentAction;

final readonly class IncidentGate
{
    public function __construct(
        private IncidentRepository $incidents,
        private IncidentAccess $access,
    ) {}

    public function load(Actor $actor, string $ulid, IncidentAction $action): IncidentRecord
    {
        // Solo restaurar necesita encontrar incidentes eliminados
        $incident = $this->incidents->find($ulid, withDeleted: $action === IncidentAction::Restore)
            ?? throw IncidentExceptions::denied(Denial::NotVisible);

        $this->ensure($actor, $incident, $action);

        return $incident;
    }

    public function ensure(Actor $actor, IncidentRecord $incident, IncidentAction $action): void
    {
        $denial = $this->access->denial($action, $actor, $incident->state());
        if ($denial !== null) {
            throw IncidentExceptions::denied($denial);
        }
    }

    // Quien guarda debe conocer la última versión; si no, otra persona cambió algo entretanto
    public function ensureVersion(IncidentRecord $incident, int $version): void
    {
        if ($incident->version !== $version) {
            throw IncidentExceptions::versionConflict();
        }
    }
}
