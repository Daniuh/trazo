<?php

declare(strict_types=1);

// Caso base de las pruebas que levantan la aplicación (API y base de datos).
// Documentado en docs/calidad/estrategia-de-pruebas.md

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Con RefreshDatabase, los seeders corren una sola vez por ejecución y cada prueba trabaja en una
    // transacción que se revierte. Sembrar en cada prueba gastaría los autoincrementales de los catálogos.
    protected bool $seed = true;

    // Clave de cifrado solo para las pruebas: al azar en cada ejecución, nunca escrita en el repositorio (es público)
    private static ?string $appKey = null;

    public function createApplication(): Application
    {
        $app = parent::createApplication();

        if ((string) $app['config']->get('app.key') === '') {
            $app['config']->set('app.key', self::$appKey ??= 'base64:'.base64_encode(random_bytes(32)));
        }

        return $app;
    }
}
