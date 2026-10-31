<?php

declare(strict_types=1);

// Recuperar y cambiar la contraseña: sin revelar qué correos existen, código de un solo uso con vencimiento e
// intentos, segundo factor cuando hay MFA, política de contraseñas (filtradas incluidas), cierre de sesiones y avisos.
// Documentado en docs/backend/auth.md

use App\Modules\Auth\Application\Mfa\SecurityEvent;
use App\Modules\Auth\Domain\LoginAttemptStore;
use App\Modules\Auth\Infrastructure\Mail\PasswordResetCode;
use App\Modules\Auth\Infrastructure\Mail\SecurityNotice;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

const RESET_EMAIL = 'agente2@trazo.test';
const OLD_PASSWORD = 'Trazo.Demo.2026';
const NEW_PASSWORD = 'Cielo-Naranja-Tranvia-42';

beforeEach(function (): void {
    Mail::fake();
    // Have I Been Pwned responde lo que la prueba deje en pwnedRanges() (por defecto, nada filtrado), o falla si se pide
    pwnedRanges([]);
    Http::fake(['api.pwnedpasswords.com/range/*' => function ($request) {
        $state = pwnedRanges();
        if ($state === null) {
            throw new ConnectionException('sin red');
        }

        return Http::response($state[substr($request->url(), -5)] ?? '');
    }]);
});

/**
 * Respuestas de Have I Been Pwned por prefijo; null simula que el servicio no responde.
 *
 * @param  array<string, string>|null|false  $set
 * @return array<string, string>|null
 */
function pwnedRanges(array|null|false $set = false): ?array
{
    static $state = [];
    if ($set !== false) {
        $state = $set;
    }

    return $state;
}

// Pide un código y devuelve el que llegó al correo
function requestResetCode(string $email = RESET_EMAIL): string
{
    test()->postJson('/api/v1/auth/password/forgot', ['email' => $email])->assertStatus(202);
    $code = '';
    Mail::assertQueued(PasswordResetCode::class, function (PasswordResetCode $mail) use (&$code): bool {
        $code = $mail->code;

        return true;
    });

    return $code;
}

function verifyResetCode(string $code, string $email = RESET_EMAIL): TestResponse
{
    return test()->postJson('/api/v1/auth/password/verify-code', ['email' => $email, 'code' => $code]);
}

/**
 * @param  array<string, string>  $extra
 */
function resetPassword(string $ticket, string $password = NEW_PASSWORD, array $extra = []): TestResponse
{
    return test()->postJson('/api/v1/auth/password/reset', [
        'email' => RESET_EMAIL,
        'ticket' => $ticket,
        'password' => $password,
        'password_confirmation' => $password,
        ...$extra,
    ]);
}

function loginWith(string $password): TestResponse
{
    app('auth')->forgetGuards();

    return test()->postJson('/api/v1/auth/login', ['email' => RESET_EMAIL, 'password' => $password]);
}

it('responde igual exista o no el correo, y solo envía el código a una cuenta real', function (): void {
    $real = $this->postJson('/api/v1/auth/password/forgot', ['email' => RESET_EMAIL]);
    $unknown = $this->postJson('/api/v1/auth/password/forgot', ['email' => 'nadie@trazo.test']);

    $real->assertStatus(202);
    $unknown->assertStatus(202);
    expect($real->getContent())->toBe($unknown->getContent());
    Mail::assertQueued(PasswordResetCode::class, 1);
    Mail::assertQueued(PasswordResetCode::class, fn (PasswordResetCode $mail): bool => $mail->hasTo(RESET_EMAIL) && preg_match('/^\d{6}$/', $mail->code) === 1);
});

it('guarda solo el HMAC del código y un código nuevo anula el anterior', function (): void {
    $first = requestResetCode();
    Mail::fake();
    $second = requestResetCode();

    expect(DB::table('password_reset_codes')->pluck('code_hash')->all())->not->toContain($first, $second);
    verifyResetCode($first)->assertUnprocessable()->assertJsonPath('code', 'PASSWORD_RESET_CODE_INVALID');
    verifyResetCode($second)->assertOk();
});

it('un código incorrecto y un correo que no existe dan el mismo error', function (): void {
    requestResetCode();

    $wrong = verifyResetCode('000000')->assertUnprocessable();
    $unknown = verifyResetCode('123456', 'nadie@trazo.test')->assertUnprocessable();

    expect($wrong->json('code'))->toBe('PASSWORD_RESET_CODE_INVALID')
        ->and(collect($wrong->json())->except('trace_id'))->toEqual(collect($unknown->json())->except('trace_id'));
});

it('anula el código tras 5 intentos fallidos', function (): void {
    $code = requestResetCode();
    $wrong = $code === '000000' ? '111111' : '000000';

    foreach (range(1, 5) as $attempt) {
        verifyResetCode($wrong)->assertUnprocessable();
    }
    // Se limpia el límite de 5 por minuto: lo que se prueba es que el código quedó anulado
    cache()->flush();

    verifyResetCode($code)->assertUnprocessable()->assertJsonPath('code', 'PASSWORD_RESET_CODE_INVALID');
});

it('el código vence a los 15 minutos', function (): void {
    $code = requestResetCode();

    $this->travel(16)->minutes();

    verifyResetCode($code)->assertUnprocessable()->assertJsonPath('code', 'PASSWORD_RESET_CODE_INVALID');
});

it('cambia la contraseña, cierra todas las sesiones, levanta el bloqueo del login y avisa por correo', function (): void {
    $session = loginWith(OLD_PASSWORD)->json('data.token');
    // Tras varios intentos fallidos, el correo quedó bloqueado
    app(LoginAttemptStore::class)->lock(hash('sha256', RESET_EMAIL), 300);
    loginWith(OLD_PASSWORD)->assertStatus(423);

    $verified = verifyResetCode(requestResetCode())->assertOk()->assertJsonPath('data.mfa_required', false);
    resetPassword($verified->json('data.ticket'))->assertNoContent();

    expect(DB::table('personal_access_tokens')->where('token', hash('sha256', explode('|', $session)[1]))->exists())->toBeFalse();
    loginWith(OLD_PASSWORD)->assertUnauthorized();
    loginWith(NEW_PASSWORD)->assertOk();
    Mail::assertQueued(SecurityNotice::class, fn (SecurityNotice $mail): bool => $mail->event === SecurityEvent::PasswordChanged);
});

it('el pase sirve una sola vez y vence a los 10 minutos', function (): void {
    $ticket = verifyResetCode(requestResetCode())->json('data.ticket');
    resetPassword($ticket)->assertNoContent();
    resetPassword($ticket, 'Otra-Clave-Distinta-77')->assertUnprocessable()->assertJsonPath('code', 'PASSWORD_RESET_EXPIRED');

    Mail::fake();
    $late = verifyResetCode(requestResetCode())->json('data.ticket');
    $this->travel(11)->minutes();
    resetPassword($late)->assertUnprocessable()->assertJsonPath('code', 'PASSWORD_RESET_EXPIRED');
});

it('aplica la política: largo mínimo, confirmación y contraseñas filtradas', function (): void {
    $ticket = verifyResetCode(requestResetCode())->json('data.ticket');

    resetPassword($ticket, 'corta')->assertUnprocessable()->assertJsonPath('errors.password.0.code', 'TOO_SHORT')
        ->assertJsonPath('errors.password.0.params.min', '12');

    $this->postJson('/api/v1/auth/password/reset', [
        'email' => RESET_EMAIL, 'ticket' => $ticket, 'password' => NEW_PASSWORD, 'password_confirmation' => 'otra-cosa-larga',
    ])->assertJsonPath('errors.password.0.code', 'CONFIRMATION_MISMATCH');

    // Have I Been Pwned dice que está filtrada: solo viajan los 5 primeros caracteres del SHA-1
    $hash = strtoupper(sha1('Filtrada-Hace-Tiempo-1'));
    pwnedRanges([substr($hash, 0, 5) => substr($hash, 5).":42\r\nAAAA:0"]);
    resetPassword($ticket, 'Filtrada-Hace-Tiempo-1')->assertJsonPath('errors.password.0.code', 'PASSWORD_BREACHED');
    Http::assertSent(fn ($request): bool => ! str_contains($request->url(), substr($hash, 5)) && $request->hasHeader('Add-Padding'));
});

it('si Have I Been Pwned no responde, igual rechaza las contraseñas más comunes y deja pasar las demás', function (): void {
    pwnedRanges(null);
    $ticket = verifyResetCode(requestResetCode())->json('data.ticket');

    resetPassword($ticket, 'Unbelievable')->assertJsonPath('errors.password.0.code', 'PASSWORD_BREACHED');
    resetPassword($ticket)->assertNoContent();
});

it('con MFA exige además el código de la aplicación o uno de recuperación', function (): void {
    $user = User::query()->where('email', RESET_EMAIL)->firstOrFail();
    DB::table('user_mfa_factors')->insert([
        'user_id' => $user->id, 'method' => 'totp', 'secret' => encrypt('JBSWY3DPEHPK3PXP', false),
        'confirmed_at' => now(), 'created_at' => now(), 'updated_at' => now(),
    ]);
    DB::table('user_recovery_codes')->insert(['user_id' => $user->id, 'code_hash' => hash('sha256', 'ABCDE-FGHJK')]);

    $ticket = verifyResetCode(requestResetCode())->assertJsonPath('data.mfa_required', true)->json('data.ticket');

    resetPassword($ticket)->assertUnprocessable()->assertJsonPath('code', 'MFA_CODE_REQUIRED');
    resetPassword($ticket, NEW_PASSWORD, ['mfa_code' => '000000'])->assertJsonPath('code', 'MFA_CODE_INVALID');
    resetPassword($ticket, NEW_PASSWORD, ['recovery_code' => 'abcde fghjk'])->assertNoContent();
    Mail::assertQueued(SecurityNotice::class, fn (SecurityNotice $mail): bool => $mail->event === SecurityEvent::RecoveryCodeUsed);
});

it('limita las solicitudes de código a 3 cada 15 minutos', function (): void {
    foreach (range(1, 3) as $attempt) {
        $this->postJson('/api/v1/auth/password/forgot', ['email' => RESET_EMAIL])->assertStatus(202);
    }

    $this->postJson('/api/v1/auth/password/forgot', ['email' => RESET_EMAIL])->assertStatus(429);
});

it('desde Mi cuenta cambia la contraseña con la actual y cierra solo las demás sesiones', function (): void {
    // Sin MFA obligatorio, para abrir dos sesiones completas seguidas
    DB::table('mfa_policies')->update(['is_required' => false]);
    $other = loginWith(OLD_PASSWORD)->json('data.token');
    $current = loginWith(OLD_PASSWORD)->json('data.token');
    $change = fn (array $body): TestResponse => (function () use ($current, $body): TestResponse {
        app('auth')->forgetGuards();

        return test()->withToken($current)->putJson('/api/v1/auth/password', $body);
    })();

    $change(['current_password' => 'no-es-esta', 'password' => NEW_PASSWORD, 'password_confirmation' => NEW_PASSWORD])
        ->assertUnprocessable()->assertJsonPath('code', 'CURRENT_PASSWORD_INVALID');
    $change(['current_password' => OLD_PASSWORD, 'password' => OLD_PASSWORD, 'password_confirmation' => OLD_PASSWORD])
        ->assertUnprocessable()->assertJsonPath('code', 'PASSWORD_SAME_AS_CURRENT');
    $change(['current_password' => OLD_PASSWORD, 'password' => NEW_PASSWORD, 'password_confirmation' => NEW_PASSWORD])
        ->assertNoContent();

    $stillOpen = fn (string $token): bool => DB::table('personal_access_tokens')->where('token', hash('sha256', explode('|', $token)[1]))->exists();
    expect($stillOpen($current))->toBeTrue()->and($stillOpen($other))->toBeFalse();
    Mail::assertQueued(SecurityNotice::class, fn (SecurityNotice $mail): bool => $mail->event === SecurityEvent::PasswordChanged);
});
