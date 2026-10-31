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
            IncidentAction::RequestReopen => $this->reopenDenial($incident) ?? $this->unless($actor->can('incidents.reopen.request')),
            IncidentAction::Reopen => $this->reopenDenial($incident) ?? $this->unless($actor->can('incidents.reopen.approve')),
            IncidentAction::DecideReopen => $this->decideDenial($actor, $incident),
            // También en lo terminado: un seguimiento no cambia el estado; lo eliminado no admite mensajes
            IncidentAction::Comment => $incident->deleted ? Denial::NotVisible : $this->unless($actor->can('incidents.comment')),
        };
    }

    // Asignar al crear: quien asigna elige a cualquiera; quien solo puede tomar incidentes, solo a sí mismo
    public function canAssignOnCreate(Actor $actor, int $assigneeId): bool
    {
        return $actor->can('incidents.assign') || ($actor->can('incidents.take') && $assigneeId === $actor->id);
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

        return VisibilityScope::for($actor)->includes($actor->id, $incident);
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

    // Reabrir o pedirlo: solo lo terminado y sin otra solicitud en curso
    private function reopenDenial(IncidentState $incident): ?Denial
    {
        return match (true) {
            $incident->deleted => Denial::NotVisible,
            ! $incident->statusIsFinal => Denial::NotFinalized,
            $incident->reopenRequestedBy !== null => Denial::ReopenPending,
            default => null,
        };
    }

    // Aprobar o rechazar: hay una solicitud pendiente, la persona puede decidir y no es quien la pidió
    private function decideDenial(Actor $actor, IncidentState $incident): ?Denial
    {
        return match (true) {
            $incident->deleted => Denial::NotVisible,
            $incident->reopenRequestedBy === null => Denial::NoReopenRequest,
            ! $actor->can('incidents.reopen.approve') => Denial::Forbidden,
            $incident->reopenRequestedBy === $actor->id => Denial::OwnReopenRequest,
            default => null,
        };
    }

    private function unless(bool $allowed): ?Denial
    {
        return $allowed ? null : Denial::Forbidden;
    }
}
