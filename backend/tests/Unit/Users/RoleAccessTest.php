<?php

declare(strict_types=1);

// Quién puede editar o eliminar roles, en qué nivel puede ubicarlos y cuándo una política de MFA se vuelve más
// permisiva. Documentado en docs/backend/users.md

use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Domain\ManagedRole;
use App\Modules\Users\Domain\MfaPolicy;
use App\Modules\Users\Domain\RoleAccess;
use App\Modules\Users\Domain\RoleAction;
use App\Modules\Users\Domain\RoleDenial;

function roleAdmin(int $level, bool $canManage = true): Administrator
{
    return new Administrator(1, $level, $canManage ? ['roles.manage'] : []);
}

function roleDenial(RoleAction $action, Administrator $admin, ManagedRole $role): ?RoleDenial
{
    return (new RoleAccess)->denial($action, $admin, $role);
}

it('sin roles.manage no se edita ningún rol', function (): void {
    expect(roleDenial(RoleAction::Update, roleAdmin(100, false), new ManagedRole(40, true, 3)))->toBe(RoleDenial::Forbidden);
});

it('el rol de la cima no se edita ni se elimina, ni siquiera desde la cima', function (): void {
    $root = new ManagedRole(100, true, 1);

    expect(roleDenial(RoleAction::Update, roleAdmin(100), $root))->toBe(RoleDenial::Locked)
        ->and(roleDenial(RoleAction::Delete, roleAdmin(100), $root))->toBe(RoleDenial::Locked);
});

it('solo se editan roles por debajo del propio nivel', function (): void {
    expect(roleDenial(RoleAction::Update, roleAdmin(80), new ManagedRole(60, true, 2)))->toBeNull()
        ->and(roleDenial(RoleAction::Update, roleAdmin(80), new ManagedRole(80, true, 2)))->toBe(RoleDenial::Outranked);
});

it('se elimina solo un rol creado que nadie tiene', function (): void {
    expect(roleDenial(RoleAction::Delete, roleAdmin(100), new ManagedRole(40, true, 0)))->toBe(RoleDenial::System)
        ->and(roleDenial(RoleAction::Delete, roleAdmin(100), new ManagedRole(50, false, 2)))->toBe(RoleDenial::InUse)
        ->and(roleDenial(RoleAction::Delete, roleAdmin(100), new ManagedRole(50, false, 0)))->toBeNull();
});

it('un rol se ubica entre 1 y 99, y por debajo de quien lo ubica', function (): void {
    $access = new RoleAccess;

    expect($access->canUseLevel(roleAdmin(100), 99))->toBeTrue()
        ->and($access->canUseLevel(roleAdmin(100), 100))->toBeFalse()
        ->and($access->canUseLevel(roleAdmin(80), 79))->toBeTrue()
        ->and($access->canUseLevel(roleAdmin(80), 80))->toBeFalse()
        ->and($access->canUseLevel(roleAdmin(100), 0))->toBeFalse();
});

it('la política de MFA se vuelve más permisiva si deja de ser obligatoria o da más gracia', function (): void {
    $current = new MfaPolicy(true, 1);

    expect($current->isRelaxedBy(new MfaPolicy(false, 1)))->toBeTrue()
        ->and($current->isRelaxedBy(new MfaPolicy(true, 2)))->toBeTrue()
        ->and($current->isRelaxedBy(new MfaPolicy(true, 0)))->toBeFalse()
        ->and((new MfaPolicy(false, 0))->isRelaxedBy(new MfaPolicy(true, 0)))->toBeFalse();
});
