<?php

declare(strict_types=1);

// Reglas de la auditoría sin base de datos: quién ve cada pestaña, el alcance por jerarquía, el rango por defecto y
// el cursor. Documentado en docs/backend/audit.md

use App\Modules\Audit\Domain\AdminAuditScope;
use App\Modules\Audit\Domain\AuditAccess;
use App\Modules\Audit\Domain\AuditCursor;
use App\Modules\Audit\Domain\AuditPeriod;
use App\Modules\Users\Domain\Administrator;

describe('acceso', function (): void {
    it('cada pestaña tiene su propio permiso', function (): void {
        $access = new AuditAccess;
        $supervisor = new Administrator(1, 60, [AuditAccess::INCIDENTS]);
        $admin = new Administrator(2, 80, [AuditAccess::ADMIN]);

        expect($access->canViewIncidents($supervisor))->toBeTrue()
            ->and($access->canViewAdmin($supervisor))->toBeFalse()
            ->and($access->canViewIncidents($admin))->toBeFalse()
            ->and($access->canViewAdmin($admin))->toBeTrue();
    });

    it('la cima ve toda la administración; los demás, solo hacia abajo', function (): void {
        $access = new AuditAccess;

        expect($access->adminScope(new Administrator(1, 100, []))->everything)->toBeTrue()
            ->and($access->adminScope(new Administrator(2, 80, []))->everything)->toBeFalse();
    });
});

describe('alcance de la administración', function (): void {
    it('incluye lo propio y lo que toca a un nivel menor', function (): void {
        $scope = AdminAuditScope::below(7, 80);

        expect($scope->includes(7, null))->toBeTrue()
            ->and($scope->includes(7, 100))->toBeTrue()
            ->and($scope->includes(3, 40))->toBeTrue()
            ->and($scope->includes(3, 0))->toBeTrue()
            ->and($scope->includes(3, 80))->toBeFalse()
            ->and($scope->includes(3, 100))->toBeFalse()
            ->and($scope->includes(3, null))->toBeFalse();
    });

    it('la cima lo incluye todo', function (): void {
        expect(AdminAuditScope::everything()->includes(3, null))->toBeTrue()
            ->and(AdminAuditScope::everything()->includes(3, 100))->toBeTrue();
    });
});

describe('rango de fechas', function (): void {
    $now = new DateTimeImmutable('2026-10-24 12:00:00');

    it('sin fechas son los últimos 30 días', function () use ($now): void {
        $period = AuditPeriod::between(null, null, $now);

        expect($period->to)->toEqual($now)->and($period->from)->toEqual(new DateTimeImmutable('2026-09-24 12:00:00'));
    });

    it('con solo el final son los 30 días anteriores; con solo el inicio, hasta ahora', function () use ($now): void {
        $to = new DateTimeImmutable('2026-06-30 23:59:59');
        $from = new DateTimeImmutable('2026-01-01 00:00:00');

        expect(AuditPeriod::between(null, $to, $now)->from)->toEqual(new DateTimeImmutable('2026-05-31 23:59:59'))
            ->and(AuditPeriod::between($from, null, $now)->to)->toEqual($now);
    });
});

describe('cursor', function (): void {
    it('va y vuelve sin perder la fecha ni el id', function (): void {
        $cursor = new AuditCursor(new DateTimeImmutable('2026-10-24 21:54:57', new DateTimeZone('UTC')), 3158);
        $decoded = AuditCursor::decode($cursor->encode());

        expect($decoded?->id)->toBe(3158)->and($decoded?->sqlDate())->toBe('2026-10-24 21:54:57');
    });

    it('rechaza uno alterado', function (string $value): void {
        expect(AuditCursor::decode($value))->toBeNull();
    })->with([
        'texto suelto' => 'hola',
        'sin id' => rtrim(base64_encode('2026-10-24 21:54:57'), '='),
        'fecha imposible' => rtrim(base64_encode('2026-13-45 99:99:99|1'), '='),
        'id con letras' => rtrim(base64_encode('2026-10-24 21:54:57|1a'), '='),
    ]);
});
