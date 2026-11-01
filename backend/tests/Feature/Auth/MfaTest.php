<?php

declare(strict_types=1);

// MFA de punta a punta: gracia de un ingreso, configuración, segundo paso, códigos de recuperación, repetición,
// límite de intentos, alcance del token de un ingreso a medias y avisos por correo.
// Documentado en docs/backend/auth.md

use App\Modules\Auth\Application\Mfa\SecurityEvent;
use App\Modules\Auth\Infrastructure\Mail\SecurityNotice;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

const MFA_EMAIL = 'agente1@trazo.test';
const MFA_PASSWORD = 'Cometa.Demo.2026';

beforeEach(function (): void {
    Mail::fake();
});

function mfaLogin(): TestResponse
{
    return test()->postJson('/api/v1/auth/login', ['email' => MFA_EMAIL, 'password' => MFA_PASSWORD]);
}

/**
 * @param  array<string, mixed>  $body
 */
function withToken(string $token, string $method, string $uri, array $body = []): TestResponse
{
    // Cada pedido con su propio token: sin esto, el guard recuerda el usuario del pedido anterior
    app('auth')->forgetGuards();

    return test()->withToken($token)->json($method, $uri, $body);
}

// Código de la aplicación para el paso actual, o uno corrido $offset pasos (30 s cada uno)
function totp(string $secret, int $offset = 0): string
{
    $google2fa = new Google2FA;

    return $google2fa->oathTotp($secret, (int) floor(time() / 30) + $offset);
}

// Configura el MFA de la persona como lo haría desde su teléfono y devuelve el secreto y los códigos de recuperación
function enrollMfa(): array
{
    $token = mfaLogin()->json('data.token');
    $secret = withToken($token, 'POST', '/api/v1/auth/mfa/setup')->json('data.secret');
    $codes = withToken($token, 'POST', '/api/v1/auth/mfa/setup/confirm', ['code' => totp($secret)])->json('data.recovery_codes');
    DB::table('personal_access_tokens')->delete();
    // El código de la configuración cuenta para el límite de 5 por minuto; cada prueba parte de cero
    cache()->flush();

    return [$secret, $codes];
}

it('el primer ingreso sugiere configurar el MFA y entrega una sesión completa', function (): void {
    $response = mfaLogin()->assertOk()
        ->assertJsonPath('data.status', 'setup_suggested')
        ->assertJsonPath('data.user.mfa.enabled', false)
        ->assertJsonPath('data.user.mfa.required', true);

    withToken($response->json('data.token'), 'GET', '/api/v1/incidents')->assertOk();
    expect(User::query()->where('email', MFA_EMAIL)->value('mfa_grace_used'))->toBe(1);
});

it('sin gracia, el token solo sirve para configurar el MFA', function (): void {
    mfaLogin();
    $response = mfaLogin()->assertOk()
        ->assertJsonPath('data.status', 'mfa_setup')
        ->assertJsonPath('data.user', null);
    $token = $response->json('data.token');

    withToken($token, 'GET', '/api/v1/incidents')->assertUnauthorized();
    withToken($token, 'GET', '/api/v1/auth/me')->assertUnauthorized();
    withToken($token, 'POST', '/api/v1/auth/mfa/setup')->assertOk();
});

it('configurar el MFA desde un ingreso a medias abre la sesión y entrega 8 códigos de recuperación', function (): void {
    mfaLogin();
    $challenge = mfaLogin()->json('data.token');
    $secret = withToken($challenge, 'POST', '/api/v1/auth/mfa/setup')
        ->assertJsonPath('data.uri', fn (string $uri): bool => str_starts_with($uri, 'otpauth://totp/Trazo:'))
        ->json('data.secret');

    $confirmed = withToken($challenge, 'POST', '/api/v1/auth/mfa/setup/confirm', ['code' => totp($secret)])
        ->assertOk()
        ->assertJsonCount(8, 'data.recovery_codes')
        ->assertJsonPath('data.session.status', 'complete');

    withToken($confirmed->json('data.session.token'), 'GET', '/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('data.mfa.enabled', true)
        ->assertJsonPath('data.mfa.recovery_codes_left', 8);
    // El token del ingreso a medias ya no existe
    withToken($challenge, 'POST', '/api/v1/auth/mfa/setup')->assertUnauthorized();
    Mail::assertQueued(SecurityNotice::class, fn (SecurityNotice $mail): bool => $mail->event === SecurityEvent::MfaEnabled);
});

it('guarda el secreto cifrado y los códigos de recuperación solo con hash', function (): void {
    [$secret, $codes] = enrollMfa();

    expect(DB::table('user_mfa_factors')->value('secret'))->not->toContain($secret)
        ->and(DB::table('user_recovery_codes')->pluck('code_hash')->all())->not->toContain($codes[0]);
});

it('con MFA, el ingreso pide el código y un código correcto abre la sesión', function (): void {
    [$secret] = enrollMfa();

    $challenge = mfaLogin()->assertJsonPath('data.status', 'mfa_verify')->json('data.token');
    withToken($challenge, 'GET', '/api/v1/incidents')->assertUnauthorized();

    withToken($challenge, 'POST', '/api/v1/auth/mfa/verify', ['code' => totp($secret, 1)])
        ->assertOk()
        ->assertJsonPath('data.status', 'complete')
        ->assertJsonPath('data.user.email', MFA_EMAIL);
});

it('el mismo código no sirve dos veces', function (): void {
    [$secret] = enrollMfa();
    $code = totp($secret, 1);

    withToken(mfaLogin()->json('data.token'), 'POST', '/api/v1/auth/mfa/verify', ['code' => $code])->assertOk();

    withToken(mfaLogin()->json('data.token'), 'POST', '/api/v1/auth/mfa/verify', ['code' => $code])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'MFA_CODE_ALREADY_USED');
});

it('cada código de recuperación sirve una sola vez y avisa por correo', function (): void {
    [, $codes] = enrollMfa();

    withToken(mfaLogin()->json('data.token'), 'POST', '/api/v1/auth/mfa/verify', ['recovery_code' => strtolower($codes[0])])
        ->assertOk();
    withToken(mfaLogin()->json('data.token'), 'POST', '/api/v1/auth/mfa/verify', ['recovery_code' => $codes[0]])
        ->assertUnprocessable()
        ->assertJsonPath('code', 'MFA_RECOVERY_CODE_INVALID');

    Mail::assertQueued(SecurityNotice::class, fn (SecurityNotice $mail): bool => $mail->event === SecurityEvent::RecoveryCodeUsed);
});

it('tras 5 códigos incorrectos corta el ingreso y revoca el token', function (): void {
    enrollMfa();
    $challenge = mfaLogin()->json('data.token');

    foreach (range(1, 4) as $attempt) {
        withToken($challenge, 'POST', '/api/v1/auth/mfa/verify', ['code' => '000000'])
            ->assertUnprocessable()
            ->assertJsonPath('code', 'MFA_CODE_INVALID')
            ->assertJsonPath('params.attempts_left', 5 - $attempt);
    }

    withToken($challenge, 'POST', '/api/v1/auth/mfa/verify', ['code' => '000000'])
        ->assertUnauthorized()
        ->assertJsonPath('code', 'MFA_TOO_MANY_ATTEMPTS');
    withToken($challenge, 'POST', '/api/v1/auth/mfa/verify', ['code' => '000000'])->assertUnauthorized();
});

it('el token de un ingreso a medias vence a los 5 minutos', function (): void {
    enrollMfa();
    $challenge = mfaLogin()->json('data.token');

    $this->travel(6)->minutes();

    withToken($challenge, 'POST', '/api/v1/auth/mfa/verify', ['code' => '123456'])->assertUnauthorized();
});

it('con una sesión completa no hay segundo paso que verificar', function (): void {
    $token = mfaLogin()->json('data.token');

    withToken($token, 'POST', '/api/v1/auth/mfa/verify', ['code' => '123456'])
        ->assertConflict()
        ->assertJsonPath('code', 'MFA_NOT_PENDING');
});

it('regenera los códigos de recuperación con un código actual e invalida los anteriores', function (): void {
    [$secret, $old] = enrollMfa();
    // Se entra con un código de recuperación para dejar libre el siguiente código de la aplicación
    $session = withToken(mfaLogin()->json('data.token'), 'POST', '/api/v1/auth/mfa/verify', ['recovery_code' => $old[7]])
        ->json('data.token');

    withToken($session, 'POST', '/api/v1/auth/mfa/recovery-codes', ['code' => '000000'])
        ->assertUnprocessable()->assertJsonPath('code', 'MFA_CODE_INVALID');
    $new = withToken($session, 'POST', '/api/v1/auth/mfa/recovery-codes', ['code' => totp($secret, 1)])
        ->assertOk()->json('data.recovery_codes');

    expect($new)->toHaveCount(8)->and(array_intersect($new, $old))->toBeEmpty();
    withToken(mfaLogin()->json('data.token'), 'POST', '/api/v1/auth/mfa/verify', ['recovery_code' => $old[1]])
        ->assertJsonPath('code', 'MFA_RECOVERY_CODE_INVALID');
});

it('no deja desactivar el MFA si el rol lo exige, y sí cuando no lo exige', function (): void {
    [$secret, $codes] = enrollMfa();
    $session = withToken(mfaLogin()->json('data.token'), 'POST', '/api/v1/auth/mfa/verify', ['recovery_code' => $codes[0]])
        ->json('data.token');

    withToken($session, 'DELETE', '/api/v1/auth/mfa', ['code' => totp($secret, 1)])
        ->assertConflict()->assertJsonPath('code', 'MFA_REQUIRED_BY_POLICY');

    DB::table('mfa_policies')->update(['is_required' => false]);
    withToken($session, 'DELETE', '/api/v1/auth/mfa', ['code' => totp($secret, 1)])->assertNoContent();

    mfaLogin()->assertJsonPath('data.status', 'complete');
    Mail::assertQueued(SecurityNotice::class, fn (SecurityNotice $mail): bool => $mail->event === SecurityEvent::MfaDisabled);
});

it('no deja empezar a configurar el MFA si ya está activo', function (): void {
    [$secret] = enrollMfa();
    $session = withToken(mfaLogin()->json('data.token'), 'POST', '/api/v1/auth/mfa/verify', ['code' => totp($secret, 1)])
        ->json('data.token');

    withToken($session, 'POST', '/api/v1/auth/mfa/setup')->assertConflict()->assertJsonPath('code', 'MFA_ALREADY_ENABLED');
});
