<?php

declare(strict_types=1);

// Qué incidentes puede ver una persona según sus permisos. Es la única definición de la regla:
// el detalle la evalúa sobre un incidente y el listado la traduce a SQL. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain;

final readonly class VisibilityScope
{
    public function __construct(
        public bool $all,
        public bool $own,
        public bool $assigned,
        public bool $unassigned,
    ) {}

    public static function for(Actor $actor): self
    {
        return new self(
            $actor->can('incidents.view.any'),
            $actor->can('incidents.view.own'),
            $actor->can('incidents.view.assigned'),
            $actor->can('incidents.view.unassigned'),
        );
    }

    public function includes(int $actorId, IncidentState $incident): bool
    {
        return $this->all
            || ($this->own && $incident->createdBy === $actorId)
            || ($this->assigned && $incident->assignedTo === $actorId)
            || ($this->unassigned && $incident->assignedTo === null);
    }

    public function isEmpty(): bool
    {
        return ! ($this->all || $this->own || $this->assigned || $this->unassigned);
    }
}
