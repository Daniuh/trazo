<?php

declare(strict_types=1);

// Bloqueo progresivo y límite de intentos del login.
// Documentado en docs/backend/auth.md

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

function attemptLogin(string $email, string $password = 'contraseña-incorrecta'): TestResponse
{
    return test()->postJson('/api/v1/auth/login', ['email' => $email, 'password' => $password]);
}

// Falla varias veces, esperando entre intentos para no chocar con el límite de 5 por minuto
function failTimes(string $email, int $times): void
{
    foreach (range(1, $times) as $_) {
        test()->travel(61)->seconds();
        attemptLogin($email);
    }
}

it('bloquea un minuto al quinto fallo seguido, incluso con la contraseña correcta', function (): void {
    failTimes('agente1@trazo.test', 4);

    attemptLogin('agente1@trazo.test')
        ->assertStatus(423)
        ->assertHeader('Retry-After', '60')
        ->assertJsonPath('code', 'ACCOUNT_LOCKED')
        ->assertJsonPath('params.retry_after', 60);

    $this->travel(30)->seconds();
    attemptLogin('agente1@trazo.test', 'Trazo.Demo.2026')->assertStatus(423);

    $this->travel(31)->seconds();
    attemptLogin('agente1@trazo.test', 'Trazo.Demo.2026')->assertOk();
});

it('bloquea igual un correo que no existe, para no revelar qué cuentas hay', function (): void {
    failTimes('nadie@trazo.test', 4);

    attemptLogin('nadie@trazo.test')->assertStatus(423)->assertJsonPath('code', 'ACCOUNT_LOCKED');
});

it('alarga la espera en cada escalón', function (): void {
    failTimes('agente1@trazo.test', 9);

    $this->travel(61)->seconds();
    attemptLogin('agente1@trazo.test')->assertStatus(423)->assertJsonPath('params.retry_after', 300);
});

it('reinicia el contador con un ingreso correcto', function (): void {
    failTimes('agente1@trazo.test', 4);
    attemptLogin('agente1@trazo.test', 'Trazo.Demo.2026')->assertOk();

    failTimes('agente1@trazo.test', 4);
    $this->travel(61)->seconds();
    attemptLogin('agente1@trazo.test', 'Trazo.Demo.2026')->assertOk();
});

it('registra el bloqueo en el log, sin el correo en claro', function (): void {
    Log::spy();

    failTimes('agente1@trazo.test', 5);

    Log::shouldHaveReceived('warning')->once()->withArgs(
        fn (string $message, array $context): bool => $context['email_hash'] === hash('sha256', 'agente1@trazo.test')
            && ! in_array('agente1@trazo.test', $context, true),
    );
});

it('limita a 5 intentos por minuto', function (): void {
    foreach (range(1, 5) as $_) {
        attemptLogin('nadie@trazo.test');
    }

    attemptLogin('nadie@trazo.test')
        ->assertTooManyRequests()
        ->assertHeader('Retry-After')
        ->assertJsonPath('code', 'TOO_MANY_REQUESTS');
});
