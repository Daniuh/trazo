<?php

declare(strict_types=1);

// Garantiza que las pruebas nunca toquen la base de desarrollo.

use Illuminate\Support\Facades\DB;

it('corre en el entorno de pruebas y contra la base trazo_test', function (): void {
    expect(app()->environment())->toBe('testing')
        ->and(DB::connection()->getDatabaseName())->toBe('trazo_test');
});
