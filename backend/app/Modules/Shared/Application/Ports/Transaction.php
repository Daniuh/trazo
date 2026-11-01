<?php

declare(strict_types=1);

// Ejecuta varias escrituras como una sola: o se guardan todas o ninguna.
// Documentado en docs/backend/shared.md

namespace App\Modules\Shared\Application\Ports;

use Closure;

interface Transaction
{
    /**
     * @template T
     *
     * @param  Closure(): T  $work
     * @return T
     */
    public function run(Closure $work): mixed;
}
