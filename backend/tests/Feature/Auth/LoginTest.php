<?php

declare(strict_types=1);

// Inicio de sesión: respuesta correcta, errores sin pistas y tokens guardados con hash.
// Documentado en docs/backend/auth.md

use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

const DEMO_PASSWORD = 'Cometa.Demo.2026';

it('entrega token, vencimiento y el usuario con sus permisos', function (): void {
    $response = $this->postJson('/api/v1/auth/login', ['email' => 'supervisor@trazo.test', 'password' => DEMO_PASSWORD]);

    $response->assertOk()
        ->assertJsonPath('data.token_type', 'Bearer')
        ->assertJsonPath('data.user.email', 'supervisor@trazo.test')
        ->assertJsonPath('data.user.roles.0.code', 'supervisor');

    $user = $response->json('data.user');
    expect($response->json('data.token'))->toContain('|trazo_')
        ->and($user['id'])->toBe(User::query()->where('email', 'supervisor@trazo.test')->value('ulid'))
        ->and($user)->not->toHaveKey('password')
        ->and($user['permissions'])->toContain('incidents.view.any')->not->toContain('system.logs.view');
});

it('acepta el correo con mayúsculas y espacios', function (): void {
    $this->postJson('/api/v1/auth/login', ['email' => '  Supervisor@TRAZO.test ', 'password' => DEMO_PASSWORD])
        ->assertOk();
});

it('guarda solo el hash del token', function (): void {
    $token = $this->postJson('/api/v1/auth/login', ['email' => 'supervisor@trazo.test', 'password' => DEMO_PASSWORD])
        ->json('data.token');

    [, $plain] = explode('|', $token, 2);
    expect(DB::table('personal_access_tokens')->value('token'))->toBe(hash('sha256', $plain))
        ->and(DB::table('personal_access_tokens')->value('tokenable_type'))->toBe('user');
});

it('responde igual con contraseña incorrecta, correo inexistente o cuenta inactiva', function (): void {
    User::factory()->inactive()->create(['email' => 'inactiva@trazo.test', 'password' => DEMO_PASSWORD]);

    $bodies = collect([
        ['supervisor@trazo.test', 'otra-contraseña'],
        ['nadie@trazo.test', DEMO_PASSWORD],
        ['inactiva@trazo.test', DEMO_PASSWORD],
    ])->map(function (array $credentials): array {
        $response = $this->postJson('/api/v1/auth/login', ['email' => $credentials[0], 'password' => $credentials[1]]);
        $response->assertUnauthorized()->assertJsonPath('code', 'INVALID_CREDENTIALS');

        return collect($response->json())->except('trace_id')->all();
    });

    expect($bodies->unique()->count())->toBe(1);
});

it('valida la forma de los datos', function (): void {
    $this->postJson('/api/v1/auth/login', ['email' => 'no-es-correo'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0.code', 'INVALID_EMAIL')
        ->assertJsonPath('errors.password.0.code', 'REQUIRED');
});
