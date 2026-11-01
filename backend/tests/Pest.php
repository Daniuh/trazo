<?php

declare(strict_types=1);

// Configuración de Pest: qué carpetas usan la aplicación completa y helpers compartidos.
// Documentado en docs/calidad/estrategia-de-pruebas.md

use App\Modules\Auth\Infrastructure\Models\UserMfaFactor;
use App\Modules\Users\Infrastructure\Models\User;
use Laravel\Sanctum\Sanctum;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');

/**
 * Módulos existentes en app/Modules, para recorrerlos en las pruebas de arquitectura.
 *
 * @return list<string>
 */
function modules(): array
{
    $dirs = glob(__DIR__.'/../app/Modules/*', GLOB_ONLYDIR) ?: [];

    return array_map('basename', $dirs);
}

/**
 * Inicia sesión como uno de los usuarios de demostración (requiere los seeders).
 */
function actingAsDemo(string $email): User
{
    $user = User::query()->where('email', $email)->firstOrFail();
    Sanctum::actingAs($user);

    return $user;
}

/**
 * Deja a la persona con el MFA ya activo, como si lo hubiera configurado en su teléfono, y devuelve su secreto.
 */
function activeMfaSecret(User $user): string
{
    $secret = (new Google2FA)->generateSecretKey();
    UserMfaFactor::query()->updateOrCreate(
        ['user_id' => $user->id],
        ['method' => 'totp', 'secret' => $secret, 'confirmed_at' => now(), 'last_used_step' => null],
    );

    return $secret;
}

// El código que la aplicación de autenticación muestra ahora para ese secreto
function currentCode(string $secret): string
{
    return (new Google2FA)->getCurrentOtp($secret);
}
