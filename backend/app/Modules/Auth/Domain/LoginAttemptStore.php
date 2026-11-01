<?php

declare(strict_types=1);

// Dónde se llevan los intentos fallidos y los bloqueos. La clave es una huella del correo, no el correo.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Domain;

interface LoginAttemptStore
{
    /** Segundos que faltan para que termine el bloqueo; 0 si no hay. */
    public function lockedFor(string $key): int;

    /** Suma un fallo y devuelve cuántos lleva seguidos. */
    public function recordFailure(string $key): int;

    public function lock(string $key, int $seconds): void;

    public function clear(string $key): void;
}
