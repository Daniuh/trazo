<?php

declare(strict_types=1);

// Reglas de arquitectura: si alguien rompe la separación de capas, estas pruebas fallan.
// Documentado en docs/arquitectura/capas.md

use App\Modules\Auth\Infrastructure\HibpBreachedPasswords;
use Illuminate\Contracts\Queue\ShouldBeEncrypted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;

arch('todo el código usa tipos estrictos')
    ->expect('App')
    ->toUseStrictTypes();

arch('no quedan funciones de depuración')
    ->expect(['dd', 'dump', 'ddd', 'ray', 'var_dump', 'print_r', 'die'])
    ->not->toBeUsed();

arch('buenas prácticas de PHP')->preset()->php();

// Única excepción: Have I Been Pwned indexa por SHA-1 y su consulta anónima lo exige. No protege ni guarda nada con él
arch('sin funciones inseguras')->preset()->security()->ignoring(HibpBreachedPasswords::class);

arch('el entorno solo se lee desde config/')
    ->expect('env')
    ->toOnlyBeUsedIn('Config');

// Un correo en cola guarda su contenido (nombres, direcciones, códigos) en Redis y, si falla, en failed_jobs
it('todo correo que sale por la cola viaja cifrado', function (): void {
    $root = realpath(__DIR__.'/../../app');
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator((string) $root, FilesystemIterator::SKIP_DOTS));
    $queued = [];

    foreach ($files as $file) {
        // Solo archivos de clase (PSR-4, con mayúscula inicial): cargar routes.php ejecutaría las rutas
        if (! ctype_upper($file->getFilename()[0])) {
            continue;
        }
        $class = 'App\\'.str_replace(['/', '.php'], ['\\', ''], substr($file->getPathname(), strlen((string) $root) + 1));
        if (is_subclass_of($class, Mailable::class) && is_subclass_of($class, ShouldQueue::class)) {
            $queued[$class] = is_subclass_of($class, ShouldBeEncrypted::class);
        }
    }

    expect($queued)->not->toBeEmpty()
        ->and(array_keys(array_filter($queued, fn (bool $encrypted): bool => ! $encrypted)))->toBe([]);
});

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
