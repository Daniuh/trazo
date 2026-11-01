<?php

declare(strict_types=1);

// Edición de roles por la API: listar, crear (nivel único, código fijo), editar con efecto real en las personas,
// jerarquía, rol de la cima bloqueado, reautenticación y eliminar solo roles creados que nadie tiene.
// Documentado en docs/backend/users.md

use App\Modules\Users\Infrastructure\Models\User;
use App\Modules\Users\Infrastructure\UserPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    cache()->flush();
});

function roleAdminAs(string $email): User
{
    app('auth')->forgetGuards();

    return actingAsDemo($email);
}

/**
 * @return array<string, mixed>
 */
function roleBody(array $overrides = []): array
{
    return [
        'code' => 'soporte_n2',
        'name' => 'Soporte nivel 2',
        'level' => 50,
        'permissions' => ['incidents.view.own', 'incidents.create', 'incidents.update.own', 'audit.admin.view'],
        'mfa_required' => true,
        'grace_logins' => 1,
        ...$overrides,
    ];
}

/**
 * @return list<string>
 */
function permissionsNowOf(string $email): array
{
    return (new UserPermissions)->of((int) User::query()->where('email', $email)->value('id'));
}

function grantTo(string $email, string $permission, string $effect = 'grant'): void
{
    DB::table('user_permissions')->insert([
        'user_id' => User::query()->where('email', $email)->value('id'),
        'permission_id' => DB::table('permissions')->where('code', $permission)->value('id'),
        'effect' => $effect,
        'granted_by' => User::query()->where('email', 'superadmin@trazo.test')->value('id'),
    ]);
}

it('lista los roles con personas, política de MFA y lo que se puede hacer con cada uno', function (): void {
    roleAdminAs('superadmin@trazo.test');

    $roles = collect($this->getJson('/api/v1/roles')->assertOk()->json('data'))->keyBy('code');

    expect($roles['agent']['members'])->toBe(3)
        ->and($roles['agent']['mfa'])->toBe(['required' => true, 'grace_logins' => 1])
        ->and($roles['agent']['actions'])->toBe(['update' => true, 'delete' => false])
        ->and($roles['super_admin']['actions'])->toBe(['update' => false, 'delete' => false])
        ->and($roles['agent']['is_system'])->toBeTrue();

    // Quien no administra ni personas ni roles no los ve
    roleAdminAs('supervisor@trazo.test');
    $this->getJson('/api/v1/roles')->assertForbidden();
});

it('crea un rol con su nivel, permisos y política de MFA, y lo registra', function (): void {
    $root = roleAdminAs('superadmin@trazo.test');

    $this->postJson('/api/v1/roles', roleBody())->assertCreated()
        ->assertJsonPath('data.code', 'soporte_n2')
        ->assertJsonPath('data.is_system', false)
        ->assertJsonPath('data.members', 0)
        ->assertJsonPath('data.actions.delete', true)
        ->assertJsonPath('data.mfa.grace_logins', 1);

    $roleId = DB::table('roles')->where('code', 'soporte_n2')->value('id');
    expect(DB::table('admin_actions')->where('target_role_id', $roleId)->where('user_id', $root->id)->exists())->toBeTrue();
});

it('el código y el nivel son únicos, y el código tiene un formato estable', function (): void {
    roleAdminAs('superadmin@trazo.test');

    $this->postJson('/api/v1/roles', roleBody(['level' => 40]))->assertUnprocessable()->assertJsonPath('errors.level.0.code', 'ALREADY_TAKEN');
    $this->postJson('/api/v1/roles', roleBody(['code' => 'agent', 'level' => 41]))->assertUnprocessable()->assertJsonPath('errors.code.0.code', 'ALREADY_TAKEN');
    $this->postJson('/api/v1/roles', roleBody(['code' => 'Soporte N2']))->assertUnprocessable()->assertJsonPath('errors.code.0.code', 'ROLE_CODE_FORMAT');
    $this->postJson('/api/v1/roles', roleBody(['level' => 100]))->assertUnprocessable()->assertJsonPath('errors.level.0.code', 'OUT_OF_RANGE');
});

it('un rol con permisos de administración exige el código de quien lo crea', function (): void {
    $secret = activeMfaSecret(roleAdminAs('superadmin@trazo.test'));
    $body = roleBody(['permissions' => ['users.view']]);

    $this->postJson('/api/v1/roles', $body)->assertUnprocessable()->assertJsonPath('code', 'REAUTHENTICATION_REQUIRED');
    $this->postJson('/api/v1/roles', [...$body, 'mfa_code' => currentCode($secret)])->assertCreated();
});

it('un administrador con roles.manage trabaja solo por debajo de su nivel y sin dar lo que no tiene', function (): void {
    grantTo('admin@trazo.test', 'roles.manage');
    roleAdminAs('admin@trazo.test');

    $this->postJson('/api/v1/roles', roleBody(['level' => 85]))->assertUnprocessable()
        ->assertJsonPath('code', 'ROLE_LEVEL_NOT_ALLOWED')->assertJsonPath('params.max', 79);
    $this->patchJson('/api/v1/roles/admin', roleBody(['code' => null, 'level' => 80]))->assertForbidden();
    $this->postJson('/api/v1/roles', roleBody(['permissions' => ['incidents.view.any']]))->assertUnprocessable()
        ->assertJsonPath('code', 'PERMISSION_NOT_GRANTABLE');
    $this->postJson('/api/v1/roles', roleBody(['level' => 70]))->assertCreated();
});

it('el rol de la cima no se edita ni se elimina', function (): void {
    roleAdminAs('superadmin@trazo.test');
    $body = roleBody();
    unset($body['code']);

    $this->patchJson('/api/v1/roles/super_admin', [...$body, 'level' => 99])->assertConflict()->assertJsonPath('code', 'ROLE_LOCKED');
    $this->deleteJson('/api/v1/roles/super_admin')->assertConflict()->assertJsonPath('code', 'ROLE_LOCKED');
});

it('editar un rol cambia de verdad los permisos de sus personas y limpia sus excepciones sobrantes', function (): void {
    grantTo('agente2@trazo.test', 'audit.incidents.view');
    grantTo('agente1@trazo.test', 'incidents.take', 'deny');
    roleAdminAs('superadmin@trazo.test');
    $agent = $this->getJson('/api/v1/roles/agent')->json('data');

    $permissions = [...array_diff($agent['permissions'], ['incidents.take']), 'audit.incidents.view'];
    $this->patchJson('/api/v1/roles/agent', [
        'name' => 'Agente', 'level' => 40, 'permissions' => array_values($permissions), 'mfa_required' => true, 'grace_logins' => 1,
    ])->assertOk();

    expect(permissionsNowOf('agente3@trazo.test'))->toContain('audit.incidents.view')->not->toContain('incidents.take')
        // El concedido que el rol ya trae y el quitado que el rol ya no trae sobran
        ->and(DB::table('user_permissions')->count())->toBe(0);
});

it('cambiar el nivel o relajar el MFA exige el código; endurecerlo no', function (): void {
    $secret = activeMfaSecret(roleAdminAs('superadmin@trazo.test'));
    $agent = $this->getJson('/api/v1/roles/agent')->json('data');
    $body = ['name' => 'Agente', 'level' => 40, 'permissions' => $agent['permissions'], 'mfa_required' => true, 'grace_logins' => 1];

    $this->patchJson('/api/v1/roles/agent', [...$body, 'level' => 45])->assertUnprocessable()->assertJsonPath('code', 'REAUTHENTICATION_REQUIRED');
    $this->patchJson('/api/v1/roles/agent', [...$body, 'mfa_required' => false])->assertUnprocessable()->assertJsonPath('code', 'REAUTHENTICATION_REQUIRED');
    $this->patchJson('/api/v1/roles/agent', [...$body, 'grace_logins' => 0])->assertOk()->assertJsonPath('data.mfa.grace_logins', 0);
    $this->patchJson('/api/v1/roles/agent', [...$body, 'grace_logins' => 0, 'level' => 45, 'mfa_code' => currentCode($secret)])
        ->assertOk()->assertJsonPath('data.level', 45);
});

it('guardar sin cambios no registra nada', function (): void {
    roleAdminAs('superadmin@trazo.test');
    $agent = $this->getJson('/api/v1/roles/agent')->json('data');

    $this->patchJson('/api/v1/roles/agent', [
        'name' => 'Agente', 'level' => 40, 'permissions' => $agent['permissions'], 'mfa_required' => true, 'grace_logins' => 1,
    ])->assertOk();

    expect(DB::table('admin_actions')->count())->toBe(0);
});

it('solo se elimina un rol creado que nadie tiene, y su historial se conserva', function (): void {
    roleAdminAs('superadmin@trazo.test');
    $this->deleteJson('/api/v1/roles/agent')->assertConflict()->assertJsonPath('code', 'ROLE_SYSTEM');

    $this->postJson('/api/v1/roles', roleBody())->assertCreated();
    $user = User::factory()->create();
    $user->roles()->attach(DB::table('roles')->where('code', 'soporte_n2')->value('id'));
    $this->deleteJson('/api/v1/roles/soporte_n2')->assertConflict()->assertJsonPath('code', 'ROLE_IN_USE');

    $user->roles()->detach();
    $this->deleteJson('/api/v1/roles/soporte_n2')->assertNoContent();

    expect(DB::table('roles')->where('code', 'soporte_n2')->exists())->toBeFalse()
        ->and(DB::table('admin_actions')->whereNull('target_role_id')->whereNull('target_user_id')->count())->toBe(2)
        ->and(DB::table('mfa_policies')->count())->toBe(5);
});
