<?php

declare(strict_types=1);

// Caso base de las pruebas que levantan la aplicación (API y base de datos).
// Documentado en docs/calidad/estrategia-de-pruebas.md

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    // Con RefreshDatabase, los seeders corren una sola vez por ejecución y cada prueba trabaja en una
    // transacción que se revierte. Sembrar en cada prueba gastaría los autoincrementales de los catálogos.
    protected bool $seed = true;
}
