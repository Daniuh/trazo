<?php

declare(strict_types=1);

// Única fuente de quién puede hacer qué con un incidente. La usan las Policies, los casos de uso y el campo
// "actions" de la API, así el front nunca calcula reglas. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain;

final class IncidentAccess
{
    /** Null si puede; si no, el motivo. */
    public function denial(IncidentAction $action, Actor $actor, IncidentState $incident): ?Denial
    {
        if (! $this->canView($actor, $incident)) {
            return Denial::NotVisible;
        }

        return match ($action) {
            IncidentAction::View => null,
            IncidentAction::Update => $this->updateDenial($actor, $incident),
            IncidentAction::ChangeStatus => $this->workDenial($actor, $incident)
                ?? $this->unless($actor->can('incidents.status.any') || ($actor->can('incidents.status.assigned') && $incident->assignedTo === $actor->id)),
            IncidentAction::Take => $this->workDenial($actor, $incident)
                ?? $this->unless($actor->can('incidents.take'))
                ?? ($incident->assignedTo !== null ? Denial::AlreadyAssigned : null),
            IncidentAction::Assign => $this->workDenial($actor, $incident) ?? $this->unless($actor->can('incidents.assign')),
            IncidentAction::Delete => $incident->deleted ? Denial::NotVisible : $this->unless($actor->can('incidents.delete')),
            IncidentAction::Restore => $this->unless($actor->can('incidents.restore')) ?? ($incident->deleted ? null : Denial::NotDeleted),
        };
    }

    /**
     * Todas las acciones evaluadas de una vez, para el campo "actions" de la API.
     *
     * @return array<string, bool>
     */
    public function actions(Actor $actor, IncidentState $incident): array
    {
        $actions = [];
        foreach (IncidentAction::cases() as $action) {
            if ($action !== IncidentAction::View) {
                $actions[$action->value] = $this->denial($action, $actor, $incident) === null;
            }
        }

        return $actions;
    }

    // Un eliminado solo lo ve quien puede restaurarlo
    private function canView(Actor $actor, IncidentState $incident): bool
    {
        if ($incident->deleted) {
            return $actor->can('incidents.restore');
        }

        return $actor->can('incidents.view.any')
            || ($actor->can('incidents.view.own') && $incident->createdBy === $actor->id)
            || ($actor->can('incidents.view.assigned') && $incident->assignedTo === $actor->id)
            || ($actor->can('incidents.view.unassigned') && $incident->assignedTo === null);
    }

    // Quien crea un incidente puede corregirlo mientras nadie lo atiende; después, solo quien gestiona cualquiera
    private function updateDenial(Actor $actor, IncidentState $incident): ?Denial
    {
        if ($denial = $this->workDenial($actor, $incident)) {
            return $denial;
        }
        if ($actor->can('incidents.update.any')) {
            return null;
        }
        if (! $actor->can('incidents.update.own') || $incident->createdBy !== $actor->id) {
            return Denial::Forbidden;
        }

        return $incident->statusIsInitial ? null : Denial::EditLocked;
    }

    // Lo que ya terminó o está eliminado no se trabaja
    private function workDenial(Actor $actor, IncidentState $incident): ?Denial
    {
        return match (true) {
            $incident->deleted => Denial::NotVisible,
            $incident->statusIsFinal => Denial::Finalized,
            default => null,
        };
    }

    private function unless(bool $allowed): ?Denial
    {
        return $allowed ? null : Denial::Forbidden;
    }
}
