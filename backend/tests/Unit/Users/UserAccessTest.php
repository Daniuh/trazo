<?php

declare(strict_types=1);

// Jerarquía de la administración: quién administra a quién, qué se puede conceder y qué exige reautenticación.
// Documentado en docs/backend/users.md

use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Domain\ManagedUser;
use App\Modules\Users\Domain\PermissionChange;
use App\Modules\Users\Domain\SensitivePermissions;
use App\Modules\Users\Domain\UserAccess;
use App\Modules\Users\Domain\UserAction;
use App\Modules\Users\Domain\UserDenial;

const ADMIN_PERMISSIONS = ['users.view', 'users.create', 'users.update', 'users.activate', 'users.roles.assign', 'users.permissions.grant', 'audit.view'];
const ROOT_PERMISSIONS = [...ADMIN_PERMISSIONS, 'users.mfa.reset', 'users.access.reset', 'roles.manage'];

function admin(int $level = 80, array $permissions = ADMIN_PERMISSIONS, int $id = 1): Administrator
{
    return new Administrator($id, $level, $permissions);
}

function person(int $level = 40, bool $active = true, bool $mfa = true, int $id = 2): ManagedUser
{
    return new ManagedUser($id, $level, $active, $mfa);
}

function userDenial(UserAction $action, Administrator $admin, ManagedUser $user): ?UserDenial
{
    return (new UserAccess)->denial($action, $admin, $user);
}

describe('jerarquía', function (): void {
    it('el administrador gestiona a quien está por debajo', function (): void {
        foreach ([UserAction::Update, UserAction::Deactivate, UserAction::AssignRole, UserAction::GrantPermissions] as $action) {
            expect(userDenial($action, admin(), person(60)))->toBeNull();
        }
    });

    it('el administrador no toca a otro administrador ni al Super admin', function (): void {
        expect(userDenial(UserAction::Update, admin(), person(80)))->toBe(UserDenial::Outranked)
            ->and(userDenial(UserAction::Deactivate, admin(), person(100)))->toBe(UserDenial::Outranked);
    });

    it('el Super admin administra incluso a otro Super admin', function (): void {
        expect(userDenial(UserAction::ResetMfa, admin(100, ROOT_PERMISSIONS), person(100)))->toBeNull()
            ->and(userDenial(UserAction::Deactivate, admin(100, ROOT_PERMISSIONS), person(100)))->toBeNull();
    });

    it('nadie se cambia a sí mismo el rol, los permisos, el estado ni el acceso, pero sí corrige sus datos', function (): void {
        $self = person(100, id: 1);
        foreach ([UserAction::AssignRole, UserAction::GrantPermissions, UserAction::Deactivate, UserAction::ResetMfa, UserAction::ResetAccess] as $action) {
            expect(userDenial($action, admin(100, ROOT_PERMISSIONS), $self))->toBe(UserDenial::Self);
        }
        expect(userDenial(UserAction::Update, admin(100, ROOT_PERMISSIONS), $self))->toBeNull();
    });

    it('un rol se da solo por debajo de quien lo da, salvo desde la cima', function (): void {
        $access = new UserAccess;

        expect($access->canGiveRole(admin(), 60))->toBeTrue()
            ->and($access->canGiveRole(admin(), 80))->toBeFalse()
            ->and($access->canGiveRole(admin(100, ROOT_PERMISSIONS), 100))->toBeTrue();
    });
});

describe('permisos y estado', function (): void {
    it('sin el permiso de la acción no se puede, aunque esté por debajo', function (): void {
        expect(userDenial(UserAction::ResetMfa, admin(), person()))->toBe(UserDenial::Forbidden)
            ->and(userDenial(UserAction::ResetAccess, admin(), person()))->toBe(UserDenial::Forbidden);
    });

    it('activar a quien ya está activo, o restablecer un MFA que no existe, chocan con su estado', function (): void {
        expect(userDenial(UserAction::Activate, admin(), person()))->toBe(UserDenial::AlreadyActive)
            ->and(userDenial(UserAction::Deactivate, admin(), person(active: false)))->toBe(UserDenial::AlreadyInactive)
            ->and(userDenial(UserAction::ResetMfa, admin(100, ROOT_PERMISSIONS), person(mfa: false)))->toBe(UserDenial::MfaNotEnabled);
    });

    it('el Super admin no lleva excepciones de permisos: su rol ya trae todo', function (): void {
        expect(userDenial(UserAction::GrantPermissions, admin(100, ROOT_PERMISSIONS), person(100)))->toBe(UserDenial::RootHasEverything);
    });
});

describe('cambio de permisos', function (): void {
    $role = ['incidents.create', 'incidents.view.own', 'incidents.update.own'];

    it('lo que difiere del rol queda como concedido o quitado, y dice qué cambió', function () use ($role): void {
        $change = PermissionChange::between($role, $role, ['incidents.create', 'incidents.view.own', 'audit.view']);

        expect($change->grants)->toBe(['audit.view'])
            ->and($change->denies)->toBe(['incidents.update.own'])
            ->and($change->added)->toBe(['audit.view'])
            ->and($change->removed)->toBe(['incidents.update.own'])
            ->and($change->isSensitive())->toBeFalse();
    });

    it('volver a los permisos del rol deja sin excepciones', function () use ($role): void {
        $change = PermissionChange::between($role, [...$role, 'audit.view'], $role);

        expect($change->grants)->toBe([])->and($change->denies)->toBe([])->and($change->removed)->toBe(['audit.view']);
    });

    it('nadie concede lo que no tiene, salvo la cima; quitar sí se puede', function () use ($role): void {
        $change = PermissionChange::between($role, $role, [...$role, 'incidents.view.any', 'audit.view']);

        expect($change->notGrantableBy(admin()))->toBe(['incidents.view.any'])
            ->and($change->notGrantableBy(admin(100, ROOT_PERMISSIONS)))->toBe([])
            ->and(PermissionChange::between($role, $role, ['incidents.create'])->notGrantableBy(admin()))->toBe([]);
    });

    it('tocar permisos de usuarios, roles o sistema es sensible', function () use ($role): void {
        expect(PermissionChange::between($role, $role, [...$role, 'users.view'])->isSensitive())->toBeTrue()
            ->and(SensitivePermissions::is('system.logs.view'))->toBeTrue()
            ->and(SensitivePermissions::is('incidents.view.any'))->toBeFalse();
    });
});
