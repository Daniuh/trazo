<?php

declare(strict_types=1);

// Configuración de Pest: qué carpetas usan la aplicación completa y helpers compartidos.
// Documentado en docs/calidad/estrategia-de-pruebas.md

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
