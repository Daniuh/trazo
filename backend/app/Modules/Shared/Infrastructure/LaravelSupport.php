<?php

declare(strict_types=1);

// Implementaciones mínimas de la hora actual y las transacciones, sobre Laravel.
// Documentado en docs/backend/shared.md

namespace App\Modules\Shared\Infrastructure;

use App\Modules\Shared\Application\Ports\Clock;
use App\Modules\Shared\Application\Ports\Transaction;
use Closure;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final class LaravelSupport implements Clock, Transaction
{
    // Usa la hora de Laravel, así las pruebas pueden "viajar en el tiempo"
    public function now(): DateTimeImmutable
    {
        return now()->toDateTimeImmutable();
    }

    public function run(Closure $work): mixed
    {
        return DB::transaction($work);
    }
}
