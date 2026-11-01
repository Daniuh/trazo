<?php

declare(strict_types=1);

// Canales del tiempo real sin base de datos: quién escucha cada uno y a cuáles se avisa cada cambio.
// Documentado en docs/backend/realtime.md

use App\Modules\Realtime\Domain\Channels;

describe('quién escucha', function (): void {
    it('el general y el de sin asignar siguen los permisos de visibilidad', function (): void {
        $supervisor = ['incidents.view.any', 'incidents.view.unassigned'];
        $agent = ['incidents.view.assigned', 'incidents.view.unassigned'];
        $requester = ['incidents.view.own'];

        expect(Channels::allows(Channels::ALL_INCIDENTS, 'u1', $supervisor))->toBeTrue()
            ->and(Channels::allows(Channels::ALL_INCIDENTS, 'u1', $agent))->toBeFalse()
            ->and(Channels::allows(Channels::UNASSIGNED_INCIDENTS, 'u1', $agent))->toBeTrue()
            ->and(Channels::allows(Channels::UNASSIGNED_INCIDENTS, 'u1', $requester))->toBeFalse();
    });

    it('el canal personal es solo de su dueño, tenga los permisos que tenga', function (): void {
        expect(Channels::allows('users.u1', 'u1', []))->toBeTrue()
            ->and(Channels::allows('users.u2', 'u1', ['incidents.view.any']))->toBeFalse()
            ->and(Channels::allows('otro.canal', 'u1', ['incidents.view.any']))->toBeFalse();
    });
});

describe('a quién se avisa', function (): void {
    it('un incidente que pasa de sin asignar a asignado llega a todos los que lo veían o lo ven', function (): void {
        expect(Channels::forIncident('creador', null, 'agente', wasUnassigned: true, isUnassigned: false))
            ->toBe(['incidents.all', 'incidents.unassigned', 'users.creador', 'users.agente']);
    });

    it('si nunca estuvo sin asignar no se avisa a ese canal, y nadie se repite', function (): void {
        expect(Channels::forIncident('agente', 'agente', 'agente', wasUnassigned: false, isUnassigned: false))
            ->toBe(['incidents.all', 'users.agente']);
    });

    it('al quitar la asignación se avisa a quien la tenía y al canal de sin asignar', function (): void {
        expect(Channels::forIncident('creador', 'agente', null, wasUnassigned: false, isUnassigned: true))
            ->toBe(['incidents.all', 'incidents.unassigned', 'users.creador', 'users.agente']);
    });
});
