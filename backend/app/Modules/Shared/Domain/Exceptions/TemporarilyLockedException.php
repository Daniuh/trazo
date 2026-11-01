<?php

declare(strict_types=1);

// Algo quedó bloqueado por un tiempo (p. ej. una cuenta tras varios intentos fallidos). Dice cuánto falta.
// Documentado en docs/backend/shared.md

namespace App\Modules\Shared\Domain\Exceptions;

class TemporarilyLockedException extends DomainException
{
    public function __construct(string $errorCode, private readonly int $retryAfterSeconds)
    {
        parent::__construct($errorCode, ['retry_after' => $retryAfterSeconds]);
    }

    public function retryAfterSeconds(): int
    {
        return $this->retryAfterSeconds;
    }
}
