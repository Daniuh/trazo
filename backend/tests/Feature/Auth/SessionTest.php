<?php

declare(strict_types=1);

// Ciclo de vida de la sesión: consultar, cerrar, vencer por tiempo o por inactividad.
// Documentado en docs/backend/auth.md

use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

function startSession(string $email = 'agente1@trazo.test'): string
{
    return test()->postJson('/api/v1/auth/login', ['email' => $email, 'password' => 'Trazo.Demo.2026'])->json('data.token');
}

// Cada llamada se resuelve como una petición nueva, sin el usuario que Laravel recuerda entre llamadas de la prueba
function me(string $token): TestResponse
{
    app('auth')->forgetGuards();

    return test()->withToken($token)->getJson('/api/v1/auth/me');
}

it('devuelve el usuario de la sesión', function (): void {
    me(startSession())->assertOk()
        ->assertJsonPath('data.email', 'agente1@trazo.test')
        ->assertJsonPath('data.roles.0.code', 'agent');
});

it('rechaza peticiones sin token o con un token inventado', function (): void {
    $this->getJson('/api/v1/auth/me')->assertUnauthorized()->assertJsonPath('code', 'UNAUTHENTICATED');
    me('1|trazo_inventado')->assertUnauthorized();
});

it('al cerrar sesión el token deja de servir', function (): void {
    $token = startSession();

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

    me($token)->assertUnauthorized();
});

it('se cierra tras una hora sin uso', function (): void {
    $token = startSession();

    $this->travel(59)->minutes();
    me($token)->assertOk();

    $this->travel(61)->minutes();
    me($token)->assertUnauthorized();
});

it('vence a las 8 horas aunque se use todo el tiempo', function (): void {
    $token = startSession();

    foreach (range(1, 9) as $_) {
        $this->travel(50)->minutes();
        me($token)->assertOk();
    }

    $this->travel(31)->minutes();
    me($token)->assertUnauthorized();
});

it('una cuenta desactivada pierde sus sesiones abiertas', function (): void {
    $token = startSession();

    User::query()->where('email', 'agente1@trazo.test')->update(['is_active' => false]);

    me($token)->assertUnauthorized();
});
