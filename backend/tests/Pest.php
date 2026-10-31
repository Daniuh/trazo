<?php

declare(strict_types=1);

// Configuración de Pest: qué carpetas usan la aplicación completa y helpers compartidos.
// Documentado en docs/calidad/estrategia-de-pruebas.md

use App\Modules\Users\Infrastructure\Models\User;
use Laravel\Sanctum\Sanctum;
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
