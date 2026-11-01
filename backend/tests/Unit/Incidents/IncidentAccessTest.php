<?php

declare(strict_types=1);

// Matriz de quién puede hacer qué con un incidente, según permisos, autoría, asignación y estado.
// Documentado en docs/backend/incidents.md

use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\Denial;
use App\Modules\Incidents\Domain\IncidentAccess;
use App\Modules\Incidents\Domain\IncidentAction;
use App\Modules\Incidents\Domain\IncidentState;

const REQUESTER = ['incidents.view.own', 'incidents.create', 'incidents.update.own'];
const AGENT = ['incidents.view.own', 'incidents.view.assigned', 'incidents.view.unassigned', 'incidents.create', 'incidents.update.own', 'incidents.take', 'incidents.status.assigned'];
const MANAGER = ['incidents.view.any', 'incidents.create', 'incidents.update.any', 'incidents.take', 'incidents.assign', 'incidents.status.any', 'incidents.delete', 'incidents.restore'];

function incident(int $createdBy = 1, ?int $assignedTo = null, bool $initial = true, bool $final = false, bool $deleted = false): IncidentState
{
    return new IncidentState($createdBy, $assignedTo, $initial, $final, $deleted);
}

function denial(IncidentAction $action, array $permissions, IncidentState $incident, int $actorId = 1): ?Denial
{
    return (new IncidentAccess)->denial($action, new Actor($actorId, $permissions), $incident);
}

describe('ver', function (): void {
    it('el solicitante ve solo lo que creó', function (): void {
        expect(denial(IncidentAction::View, REQUESTER, incident(createdBy: 1)))->toBeNull()
            ->and(denial(IncidentAction::View, REQUESTER, incident(createdBy: 2)))->toBe(Denial::NotVisible);
    });

    it('el agente ve lo suyo, lo asignado a él y lo que nadie tiene', function (): void {
        expect(denial(IncidentAction::View, AGENT, incident(createdBy: 2, assignedTo: 1)))->toBeNull()
            ->and(denial(IncidentAction::View, AGENT, incident(createdBy: 2)))->toBeNull()
            ->and(denial(IncidentAction::View, AGENT, incident(createdBy: 2, assignedTo: 3)))->toBe(Denial::NotVisible);
    });

    it('un eliminado solo lo ve quien puede restaurarlo', function (): void {
        expect(denial(IncidentAction::View, REQUESTER, incident(deleted: true)))->toBe(Denial::NotVisible)
            ->and(denial(IncidentAction::Restore, MANAGER, incident(deleted: true)))->toBeNull();
    });
});

describe('editar', function (): void {
    it('quien lo creó lo edita mientras está abierto', function (): void {
        expect(denial(IncidentAction::Update, REQUESTER, incident()))->toBeNull()
            ->and(denial(IncidentAction::Update, REQUESTER, incident(initial: false)))->toBe(Denial::EditLocked);
    });

    it('el supervisor edita cualquiera que no haya terminado', function (): void {
        expect(denial(IncidentAction::Update, MANAGER, incident(createdBy: 9, initial: false)))->toBeNull()
            ->and(denial(IncidentAction::Update, MANAGER, incident(initial: false, final: true)))->toBe(Denial::Finalized);
    });

    it('un agente no edita lo que no creó', function (): void {
        expect(denial(IncidentAction::Update, AGENT, incident(createdBy: 2, assignedTo: 1)))->toBe(Denial::Forbidden);
    });
});

describe('estado y asignación', function (): void {
    it('el agente cambia el estado solo de lo asignado a él', function (): void {
        expect(denial(IncidentAction::ChangeStatus, AGENT, incident(createdBy: 2, assignedTo: 1)))->toBeNull()
            ->and(denial(IncidentAction::ChangeStatus, AGENT, incident(createdBy: 2)))->toBe(Denial::Forbidden);
    });

    it('solo se toma lo que nadie tiene', function (): void {
        expect(denial(IncidentAction::Take, AGENT, incident(createdBy: 2)))->toBeNull()
            ->and(denial(IncidentAction::Take, MANAGER, incident(createdBy: 2, assignedTo: 3)))->toBe(Denial::AlreadyAssigned);
    });

    it('lo terminado ya no se trabaja', function (): void {
        $closed = incident(createdBy: 2, assignedTo: 1, initial: false, final: true);

        expect(denial(IncidentAction::ChangeStatus, MANAGER, $closed))->toBe(Denial::Finalized)
            ->and(denial(IncidentAction::Assign, MANAGER, $closed))->toBe(Denial::Finalized);
    });

    it('al crear, quien toma incidentes solo se asigna a sí mismo; quien asigna, a cualquiera', function (): void {
        $access = new IncidentAccess;

        expect($access->canAssignOnCreate(new Actor(1, AGENT), 1))->toBeTrue()
            ->and($access->canAssignOnCreate(new Actor(1, AGENT), 2))->toBeFalse()
            ->and($access->canAssignOnCreate(new Actor(1, MANAGER), 2))->toBeTrue()
            ->and($access->canAssignOnCreate(new Actor(1, REQUESTER), 1))->toBeFalse();
    });

    it('el solicitante no asigna, no cambia estados ni elimina', function (): void {
        foreach ([IncidentAction::Assign, IncidentAction::ChangeStatus, IncidentAction::Delete] as $action) {
            expect(denial($action, REQUESTER, incident()))->toBe(Denial::Forbidden);
        }
    });
});

describe('reabrir', function (): void {
    $closed = fn (?int $requestedBy = null): IncidentState => new IncidentState(1, 2, false, true, false, $requestedBy);

    it('se pide solo sobre lo terminado y sin otra solicitud en curso', function () use ($closed): void {
        expect(denial(IncidentAction::RequestReopen, [...REQUESTER, 'incidents.reopen.request'], $closed()))->toBeNull()
            ->and(denial(IncidentAction::RequestReopen, REQUESTER, $closed()))->toBe(Denial::Forbidden)
            ->and(denial(IncidentAction::RequestReopen, [...REQUESTER, 'incidents.reopen.request'], incident()))->toBe(Denial::NotFinalized)
            ->and(denial(IncidentAction::RequestReopen, [...REQUESTER, 'incidents.reopen.request'], $closed(1)))->toBe(Denial::ReopenPending);
    });

    it('reabre directo quien puede aprobar, si no hay una solicitud esperando', function () use ($closed): void {
        $approver = [...MANAGER, 'incidents.reopen.approve'];

        expect(denial(IncidentAction::Reopen, $approver, $closed(), 9))->toBeNull()
            ->and(denial(IncidentAction::Reopen, MANAGER, $closed(), 9))->toBe(Denial::Forbidden)
            ->and(denial(IncidentAction::Reopen, $approver, $closed(1), 9))->toBe(Denial::ReopenPending);
    });

    it('decide quien puede aprobar, menos quien la pidió', function () use ($closed): void {
        $approver = [...MANAGER, 'incidents.reopen.approve'];

        expect(denial(IncidentAction::DecideReopen, $approver, $closed(1), 9))->toBeNull()
            ->and(denial(IncidentAction::DecideReopen, $approver, $closed(9), 9))->toBe(Denial::OwnReopenRequest)
            ->and(denial(IncidentAction::DecideReopen, $approver, $closed(), 9))->toBe(Denial::NoReopenRequest)
            ->and(denial(IncidentAction::DecideReopen, MANAGER, $closed(1), 9))->toBe(Denial::Forbidden);
    });

    it('lo eliminado no se reabre', function (): void {
        $deleted = new IncidentState(1, null, false, true, true, 2);

        foreach ([IncidentAction::RequestReopen, IncidentAction::Reopen, IncidentAction::DecideReopen] as $action) {
            expect(denial($action, [...MANAGER, 'incidents.reopen.request', 'incidents.reopen.approve'], $deleted, 9))->toBe(Denial::NotVisible);
        }
    });
});

it('resume todas las acciones para la API', function (): void {
    $actions = (new IncidentAccess)->actions(new Actor(1, REQUESTER), incident());

    expect($actions)->toBe([
        'update' => true, 'change_status' => false, 'take' => false, 'assign' => false, 'delete' => false, 'restore' => false,
        'request_reopen' => false, 'reopen' => false, 'decide_reopen' => false, 'comment' => false,
    ]);
});
