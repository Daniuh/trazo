<?php

declare(strict_types=1);

// Administración de usuarios por la API: listar, ver, crear (con bienvenida y sin contraseña), editar, activar y
// desactivar, y la protección contra averiguar correos sin permiso. Documentado en docs/backend/users.md

use App\Modules\Users\Infrastructure\Mail\AccountNotice;
use App\Modules\Users\Infrastructure\Mail\AccountWelcome;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Mail::fake();
});

// Cambia de persona entre pedidos: sin esto, el guard recuerda a la anterior
function signInAsDemo(string $email): User
{
    app('auth')->forgetGuards();

    return actingAsDemo($email);
}

function demoUlid(string $email): string
{
    return User::query()->where('email', $email)->value('ulid');
}

it('solo quien puede ver usuarios entra a la administración', function (): void {
    signInAsDemo('supervisor@trazo.test');
    $this->getJson('/api/v1/users')->assertForbidden()->assertJsonPath('code', 'FORBIDDEN_ACTION');

    signInAsDemo('admin@trazo.test');
    $this->getJson('/api/v1/users')->assertOk()->assertJsonPath('meta.total', User::query()->count());
});

it('lista con búsqueda, filtros y orden', function (): void {
    signInAsDemo('admin@trazo.test');

    $this->getJson('/api/v1/users?search=agente&sort=-email')->assertOk()
        ->assertJsonPath('meta.total', 3)
        ->assertJsonPath('data.0.email', 'agente3@trazo.test');

    $this->getJson('/api/v1/users?filter[role]=requester,supervisor')->assertOk()->assertJsonPath('meta.total', 4);
    $this->getJson('/api/v1/users?filter[status]=inactive')->assertOk()->assertJsonPath('meta.total', 0);

    // Un % escrito se busca tal cual, no como comodín
    $this->getJson('/api/v1/users?search=%25')->assertOk()->assertJsonPath('meta.total', 0);
});

it('la ficha trae sus permisos y lo que quien administra puede hacer con ella', function (): void {
    signInAsDemo('admin@trazo.test');

    $this->getJson('/api/v1/users/'.demoUlid('agente1@trazo.test'))->assertOk()
        ->assertJsonPath('data.role.code', 'agent')
        ->assertJsonPath('data.permissions.granted', [])
        ->assertJsonPath('data.actions.assign_role', true)
        ->assertJsonPath('data.actions.reset_mfa', false)
        ->assertJsonPath('data.is_self', false)
        ->assertJsonMissingPath('data.password');

    // Sobre el Super admin, nada
    $actions = $this->getJson('/api/v1/users/'.demoUlid('superadmin@trazo.test'))->assertOk()->json('data.actions');
    expect(array_filter($actions))->toBe([]);
});

it('crea una cuenta sin contraseña conocida y le envía la bienvenida', function (): void {
    $admin = signInAsDemo('admin@trazo.test');

    $response = $this->postJson('/api/v1/users', ['name' => '  Ana   Torres ', 'email' => ' Ana.Torres@Empresa.COM ', 'role' => 'agent'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Ana Torres')
        ->assertJsonPath('data.email', 'ana.torres@empresa.com')
        ->assertJsonPath('data.role.code', 'agent')
        ->assertJsonPath('data.mfa_enabled', false);

    $user = User::query()->where('ulid', $response->json('data.id'))->firstOrFail();
    expect(Hash::check('Trazo.Demo.2026', $user->getAuthPassword()))->toBeFalse()
        ->and(DB::table('admin_actions')->where('target_user_id', $user->id)->where('user_id', $admin->id)->exists())->toBeTrue();

    Mail::assertQueued(AccountWelcome::class, fn (AccountWelcome $mail): bool => $mail->hasTo('ana.torres@empresa.com')
        && str_ends_with($mail->createPasswordUrl, '/recuperar') && $mail->role === 'Agente');
});

it('no deja crear con un correo repetido ni con un rol de su nivel o superior', function (): void {
    signInAsDemo('admin@trazo.test');

    $this->postJson('/api/v1/users', ['name' => 'Otra', 'email' => 'AGENTE1@trazo.test', 'role' => 'agent'])
        ->assertUnprocessable()->assertJsonPath('errors.email.0.code', 'ALREADY_TAKEN');

    $this->postJson('/api/v1/users', ['name' => 'Otra', 'email' => 'otra@empresa.com', 'role' => 'admin'])
        ->assertUnprocessable()->assertJsonPath('code', 'ROLE_NOT_ASSIGNABLE');
});

it('sin permiso no se puede averiguar si un correo existe', function (): void {
    signInAsDemo('agente1@trazo.test');

    $this->postJson('/api/v1/users', ['name' => 'X', 'email' => 'supervisor@trazo.test', 'role' => 'agent'])
        ->assertForbidden()->assertJsonMissingPath('errors');
});

it('edita nombre y correo, y avisa a la dirección anterior', function (): void {
    signInAsDemo('admin@trazo.test');
    $ulid = demoUlid('solicitante1@trazo.test');

    $this->patchJson("/api/v1/users/{$ulid}", ['name' => 'Juan Pérez Gómez', 'email' => 'juan.perez@empresa.com'])
        ->assertOk()->assertJsonPath('data.email', 'juan.perez@empresa.com');

    Mail::assertQueued(AccountNotice::class, fn (AccountNotice $mail): bool => $mail->hasTo('solicitante1@trazo.test')
        && $mail->event->value === 'email_changed');

    // Su propio correo, sin chocar consigo mismo
    $this->patchJson("/api/v1/users/{$ulid}", ['name' => 'Juan Pérez Gómez', 'email' => 'juan.perez@empresa.com'])->assertOk();
});

it('al desactivar cierra sus sesiones al instante y no la deja entrar', function (): void {
    $agent = User::query()->where('email', 'agente2@trazo.test')->firstOrFail();
    $token = $agent->createToken('session', ['session'])->plainTextToken;
    signInAsDemo('admin@trazo.test');

    $this->postJson('/api/v1/users/'.$agent->ulid.'/deactivate')->assertOk()
        ->assertJsonPath('data.is_active', false)
        ->assertJsonPath('data.actions.activate', true);

    expect(DB::table('personal_access_tokens')->where('tokenable_id', $agent->id)->count())->toBe(0);
    Mail::assertQueued(AccountNotice::class, fn (AccountNotice $mail): bool => $mail->event->value === 'deactivated');

    app('auth')->forgetGuards();
    $this->withToken($token)->getJson('/api/v1/auth/me')->assertUnauthorized();
    $this->postJson('/api/v1/auth/login', ['email' => 'agente2@trazo.test', 'password' => 'Trazo.Demo.2026'])->assertUnauthorized();
});

it('desactivar a quien ya lo está choca con su estado', function (): void {
    signInAsDemo('admin@trazo.test');
    $ulid = demoUlid('agente2@trazo.test');

    $this->postJson("/api/v1/users/{$ulid}/deactivate")->assertOk();
    $this->postJson("/api/v1/users/{$ulid}/deactivate")->assertConflict()->assertJsonPath('code', 'USER_ALREADY_INACTIVE');
    $this->postJson("/api/v1/users/{$ulid}/activate")->assertOk()->assertJsonPath('data.is_active', true);
});

it('la ficha cuenta sus incidentes pendientes asignados, para avisar antes de desactivarla', function (): void {
    signInAsDemo('admin@trazo.test');
    $agent = User::query()->where('email', 'agente1@trazo.test')->firstOrFail();
    $pending = DB::table('incidents')->join('statuses', 'statuses.id', '=', 'incidents.status_id')
        ->where('assigned_to', $agent->id)->where('statuses.is_pending', true)->whereNull('deleted_at')->count();

    $this->getJson("/api/v1/users/{$agent->ulid}")->assertOk()->assertJsonPath('data.pending_assigned_incidents', $pending);
});

it('un ingreso completo anota la fecha del último ingreso', function (): void {
    $this->postJson('/api/v1/auth/login', ['email' => 'solicitante2@trazo.test', 'password' => 'Trazo.Demo.2026'])->assertOk();

    expect(User::query()->where('email', 'solicitante2@trazo.test')->value('last_login_at'))->not->toBeNull();
});
