<?php

declare(strict_types=1);

// Acceso de las personas por la API: jerarquía, roles, permisos por persona (concedidos y quitados) y su efecto real,
// reautenticación con el código de quien administra, restablecer MFA y acceso, y que nunca falte un Super admin.
// Documentado en docs/backend/users.md

use App\Modules\Users\Infrastructure\Mail\AccountNotice;
use App\Modules\Users\Infrastructure\Models\User;
use App\Modules\Users\Infrastructure\PermissionSql;
use App\Modules\Users\Infrastructure\UserPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Mail::fake();
    cache()->flush();
});

function adminAs(string $email): User
{
    app('auth')->forgetGuards();

    return actingAsDemo($email);
}

function managedUlid(string $email): string
{
    return User::query()->where('email', $email)->value('ulid');
}

/**
 * @return list<string>
 */
function freshPermissionsOf(string $email): array
{
    return (new UserPermissions)->of((int) User::query()->where('email', $email)->value('id'));
}

describe('jerarquía', function (): void {
    it('el administrador no toca a otro administrador ni al Super admin', function (): void {
        adminAs('admin@trazo.test');

        $this->patchJson('/api/v1/users/'.managedUlid('superadmin@trazo.test'), ['name' => 'Otro nombre', 'email' => 'superadmin@trazo.test'])
            ->assertForbidden()->assertJsonPath('code', 'USER_OUTRANKED');
        $this->postJson('/api/v1/users/'.managedUlid('superadmin@trazo.test').'/deactivate')
            ->assertForbidden()->assertJsonPath('code', 'USER_OUTRANKED');
    });

    it('nadie cambia su propio rol', function (): void {
        adminAs('admin@trazo.test');

        $this->putJson('/api/v1/users/'.managedUlid('admin@trazo.test').'/roles', ['role' => 'requester'])
            ->assertForbidden()->assertJsonPath('code', 'USER_SELF_MANAGEMENT');
    });

    it('el administrador cambia roles por debajo de su nivel, sin reautenticarse', function (): void {
        adminAs('admin@trazo.test');

        $this->putJson('/api/v1/users/'.managedUlid('agente1@trazo.test').'/roles', ['role' => 'supervisor'])
            ->assertOk()->assertJsonPath('data.role.code', 'supervisor');
        $this->putJson('/api/v1/users/'.managedUlid('agente2@trazo.test').'/roles', ['role' => 'admin'])
            ->assertUnprocessable()->assertJsonPath('code', 'ROLE_NOT_ASSIGNABLE');

        Mail::assertQueued(AccountNotice::class, fn (AccountNotice $mail): bool => $mail->event->value === 'role_changed'
            && $mail->params['role'] === 'Supervisor');
    });

    it('los roles disponibles dicen cuáles puede dar quien consulta', function (): void {
        adminAs('admin@trazo.test');

        $roles = collect($this->getJson('/api/v1/roles')->assertOk()->json('data'))->pluck('assignable', 'code');

        expect($roles->all())->toBe(['super_admin' => false, 'admin' => false, 'supervisor' => true, 'agent' => true, 'requester' => true]);
    });
});

describe('reautenticación', function (): void {
    it('dar un rol con permisos de administración exige el código de quien lo da', function (): void {
        $root = adminAs('superadmin@trazo.test');
        $secret = activeMfaSecret($root);
        $agent = managedUlid('agente3@trazo.test');

        $this->putJson("/api/v1/users/{$agent}/roles", ['role' => 'admin'])
            ->assertUnprocessable()->assertJsonPath('code', 'REAUTHENTICATION_REQUIRED');
        $this->putJson("/api/v1/users/{$agent}/roles", ['role' => 'admin', 'mfa_code' => '000000'])
            ->assertUnprocessable()->assertJsonPath('code', 'MFA_CODE_INVALID')->assertJsonPath('params.attempts_left', 4);
        $this->putJson("/api/v1/users/{$agent}/roles", ['role' => 'admin', 'mfa_code' => currentCode($secret)])
            ->assertOk()->assertJsonPath('data.role.code', 'admin');

        // El mismo código no sirve dos veces
        $this->putJson("/api/v1/users/{$agent}/roles", ['role' => 'agent', 'mfa_code' => currentCode($secret)])
            ->assertUnprocessable()->assertJsonPath('code', 'MFA_CODE_ALREADY_USED');
    });

    it('quien no tiene MFA no puede confirmar una acción sensible', function (): void {
        adminAs('superadmin@trazo.test');
        activeMfaSecret(User::query()->where('email', 'agente1@trazo.test')->firstOrFail());

        $this->deleteJson('/api/v1/users/'.managedUlid('agente1@trazo.test').'/mfa', ['mfa_code' => '123456'])
            ->assertUnprocessable()->assertJsonPath('code', 'REAUTHENTICATION_UNAVAILABLE');
    });

    it('cinco códigos incorrectos pausan la reautenticación', function (): void {
        $root = adminAs('superadmin@trazo.test');
        activeMfaSecret($root);
        $agent = managedUlid('agente1@trazo.test');

        foreach (range(1, 5) as $ignored) {
            $this->postJson("/api/v1/users/{$agent}/access-reset", ['mfa_code' => '000000'])->assertUnprocessable();
        }

        $this->postJson("/api/v1/users/{$agent}/access-reset", ['mfa_code' => '000000'])
            ->assertStatus(423)->assertJsonPath('code', 'REAUTHENTICATION_LOCKED')->assertHeader('Retry-After');
    });
});

describe('permisos por persona', function (): void {
    it('concede uno adicional y quita uno del rol, con efecto real en la sesión', function (): void {
        adminAs('admin@trazo.test');
        $agent = managedUlid('agente1@trazo.test');
        $current = $this->getJson("/api/v1/users/{$agent}")->json('data.permissions.effective');
        $desired = [...array_diff($current, ['incidents.take']), 'audit.admin.view'];

        $this->putJson("/api/v1/users/{$agent}/permissions", ['permissions' => array_values($desired)])->assertOk()
            ->assertJsonPath('data.permissions.granted', ['audit.admin.view'])
            ->assertJsonPath('data.permissions.denied', ['incidents.take']);

        expect(freshPermissionsOf('agente1@trazo.test'))->toContain('audit.admin.view')->not->toContain('incidents.take');
        expect(DB::table('user_permissions')->where('effect', 'grant')->value('granted_by'))
            ->toBe(User::query()->where('email', 'admin@trazo.test')->value('id'));
        Mail::assertQueued(AccountNotice::class, fn (AccountNotice $mail): bool => $mail->event->value === 'permissions_changed');
    });

    it('nadie concede lo que no tiene', function (): void {
        adminAs('admin@trazo.test');
        $agent = managedUlid('agente1@trazo.test');
        $current = $this->getJson("/api/v1/users/{$agent}")->json('data.permissions.effective');

        $this->putJson("/api/v1/users/{$agent}/permissions", ['permissions' => [...$current, 'incidents.view.any']])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'PERMISSION_NOT_GRANTABLE')
            ->assertJsonPath('params.permissions', 'incidents.view.any');
    });

    it('un administrador "completo" puede restablecer el MFA si el Super admin se lo concede', function (): void {
        $root = adminAs('superadmin@trazo.test');
        $rootSecret = activeMfaSecret($root);
        $admin = managedUlid('admin@trazo.test');
        $current = $this->getJson("/api/v1/users/{$admin}")->json('data.permissions.effective');

        // Conceder un permiso sensible exige el código del Super admin
        $this->putJson("/api/v1/users/{$admin}/permissions", ['permissions' => [...$current, 'users.mfa.reset']])
            ->assertUnprocessable()->assertJsonPath('code', 'REAUTHENTICATION_REQUIRED');
        $this->putJson("/api/v1/users/{$admin}/permissions", ['permissions' => [...$current, 'users.mfa.reset'], 'mfa_code' => currentCode($rootSecret)])
            ->assertOk()->assertJsonPath('data.permissions.granted', ['users.mfa.reset']);

        $agent = User::query()->where('email', 'agente1@trazo.test')->firstOrFail();
        activeMfaSecret($agent);
        $adminSecret = activeMfaSecret(adminAs('admin@trazo.test'));

        $this->deleteJson("/api/v1/users/{$agent->ulid}/mfa", ['mfa_code' => currentCode($adminSecret)])
            ->assertOk()->assertJsonPath('data.mfa_enabled', false);
        expect(DB::table('user_recovery_codes')->where('user_id', $agent->id)->count())->toBe(0);
        Mail::assertQueued(AccountNotice::class, fn (AccountNotice $mail): bool => $mail->event->value === 'mfa_reset');
    });

    it('el Super admin no lleva excepciones de permisos', function (): void {
        adminAs('superadmin@trazo.test');
        $other = User::factory()->create();
        $other->roles()->attach(DB::table('roles')->where('code', 'super_admin')->value('id'));

        $this->putJson("/api/v1/users/{$other->ulid}/permissions", ['permissions' => []])
            ->assertConflict()->assertJsonPath('code', 'USER_ROOT_FULL_ACCESS');
    });

    it('al cambiar de rol se quitan las excepciones que el rol nuevo vuelve innecesarias', function (): void {
        adminAs('superadmin@trazo.test');
        $agent = managedUlid('agente1@trazo.test');
        $current = $this->getJson("/api/v1/users/{$agent}")->json('data.permissions.effective');
        $this->putJson("/api/v1/users/{$agent}/permissions", ['permissions' => [...$current, 'audit.incidents.view']])->assertOk();

        // El supervisor ya trae la auditoría de incidentes: el concedido sobra
        $this->putJson("/api/v1/users/{$agent}/roles", ['role' => 'supervisor'])->assertOk()
            ->assertJsonPath('data.permissions.granted', []);
    });

    it('quitarle a alguien el permiso de atender incidentes lo saca del selector de "asignar a"', function (): void {
        adminAs('admin@trazo.test');
        $agent = managedUlid('agente1@trazo.test');
        $current = $this->getJson("/api/v1/users/{$agent}")->json('data.permissions.effective');
        $this->putJson("/api/v1/users/{$agent}/permissions", ['permissions' => array_values(array_diff($current, ['incidents.status.assigned']))])->assertOk();

        adminAs('supervisor@trazo.test');
        $assignees = collect($this->getJson('/api/v1/incidents/assignees')->assertOk()->json('data'))->pluck('id');

        expect($assignees)->not->toContain($agent)->toContain(managedUlid('agente2@trazo.test'));
    });
});

describe('restablecer y proteger el acceso', function (): void {
    it('restablecer el acceso invalida la contraseña, cierra sesiones y avisa cómo crear una nueva', function (): void {
        $root = adminAs('superadmin@trazo.test');
        $secret = activeMfaSecret($root);
        $agent = User::query()->where('email', 'agente2@trazo.test')->firstOrFail();
        $agent->createToken('session', ['session']);

        $this->postJson("/api/v1/users/{$agent->ulid}/access-reset", ['mfa_code' => currentCode($secret)])->assertOk();

        expect(Hash::check('Trazo.Demo.2026', (string) User::query()->whereKey($agent->id)->value('password')))->toBeFalse()
            ->and(DB::table('personal_access_tokens')->where('tokenable_id', $agent->id)->count())->toBe(0);
        Mail::assertQueued(AccountNotice::class, fn (AccountNotice $mail): bool => $mail->event->value === 'access_reset'
            && str_ends_with($mail->params['url'], '/recuperar'));
    });

    it('el administrador no restablece accesos si no se lo conceden', function (): void {
        adminAs('admin@trazo.test');

        $this->postJson('/api/v1/users/'.managedUlid('agente1@trazo.test').'/access-reset', ['mfa_code' => '123456'])
            ->assertForbidden()->assertJsonPath('code', 'FORBIDDEN_ACTION');
    });

    it('con dos Super admin activos, uno puede desactivar al otro', function (): void {
        adminAs('superadmin@trazo.test');
        $other = User::factory()->create();
        $other->roles()->attach(DB::table('roles')->where('code', 'super_admin')->value('id'));

        $this->postJson("/api/v1/users/{$other->ulid}/deactivate")->assertOk();
    });

    it('nunca queda el sistema sin un Super admin activo', function (): void {
        $root = adminAs('superadmin@trazo.test');
        $secret = activeMfaSecret($root);
        $other = User::factory()->create();
        $other->roles()->attach(DB::table('roles')->where('code', 'super_admin')->value('id'));

        // Otra persona desactivó a quien administra justo antes (dos cambios a la vez): el otro es el último activo
        DB::table('users')->where('id', $root->id)->update(['is_active' => false]);

        $this->postJson("/api/v1/users/{$other->ulid}/deactivate")
            ->assertConflict()->assertJsonPath('code', 'LAST_SUPER_ADMIN');
        $this->putJson("/api/v1/users/{$other->ulid}/roles", ['role' => 'agent', 'mfa_code' => currentCode($secret)])
            ->assertConflict()->assertJsonPath('code', 'LAST_SUPER_ADMIN');
        expect(User::query()->whereKey($other->id)->value('is_active'))->toBeTrue();
    });
});

it('la regla de permisos en SQL coincide con la de la sesión para cada persona y permiso', function (): void {
    $agent = User::query()->where('email', 'agente1@trazo.test')->firstOrFail();
    $admin = User::query()->where('email', 'admin@trazo.test')->firstOrFail();
    $permission = fn (string $code): int => (int) DB::table('permissions')->where('code', $code)->value('id');
    DB::table('user_permissions')->insert([
        ['user_id' => $agent->id, 'permission_id' => $permission('audit.admin.view'), 'effect' => 'grant', 'granted_by' => $admin->id],
        ['user_id' => $agent->id, 'permission_id' => $permission('incidents.take'), 'effect' => 'deny', 'granted_by' => $admin->id],
        // Concedido y a la vez quitado por su rol no aplica aquí; un quitado que el rol no trae no cambia nada
        ['user_id' => $admin->id, 'permission_id' => $permission('incidents.assign'), 'effect' => 'deny', 'granted_by' => $agent->id],
    ]);

    foreach (DB::table('permissions')->pluck('code') as $code) {
        $query = DB::table('users');
        PermissionSql::havingPermission($query, (string) $code);
        $bySql = $query->pluck('users.id')->map(fn (mixed $id): int => (int) $id)->sort()->values()->all();

        $bySession = User::query()->pluck('id')
            ->filter(fn (int $id): bool => in_array($code, (new UserPermissions)->of($id), true))
            ->sort()->values()->all();

        expect($bySql)->toBe($bySession, "Permiso {$code}");
    }
});
