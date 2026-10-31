<?php

declare(strict_types=1);

// Reglas de arquitectura: si alguien rompe la separación de capas, estas pruebas fallan.
// Documentado en docs/arquitectura/capas.md

arch('todo el código usa tipos estrictos')
    ->expect('App')
    ->toUseStrictTypes();

arch('no quedan funciones de depuración')
    ->expect(['dd', 'dump', 'ddd', 'ray', 'var_dump', 'print_r', 'die'])
    ->not->toBeUsed();

arch('buenas prácticas de PHP')->preset()->php();

arch('sin funciones inseguras')->preset()->security();

arch('el entorno solo se lee desde config/')
    ->expect('env')
    ->toOnlyBeUsedIn('Config');

foreach (modules() as $module) {
    $base = "App\\Modules\\{$module}";

    arch("{$module}: el dominio no depende del framework ni de otras capas")
        ->expect("{$base}\\Domain")
        ->not->toUse(['Illuminate', 'Laravel', "{$base}\\Application", "{$base}\\Infrastructure", "{$base}\\Http"]);

    arch("{$module}: la aplicación no conoce HTTP ni Eloquent")
        ->expect("{$base}\\Application")
        ->not->toUse(["{$base}\\Http", 'Illuminate\\Http', 'Illuminate\\Database\\Eloquent']);

    arch("{$module}: la capa HTTP no consulta la base de datos directamente")
        ->expect("{$base}\\Http")
        ->not->toUse(['Illuminate\\Support\\Facades\\DB', 'Illuminate\\Database\\Eloquent\\Builder']);
}
