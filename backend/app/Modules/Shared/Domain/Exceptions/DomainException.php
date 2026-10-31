<?php

declare(strict_types=1);

// Base de las excepciones de negocio: llevan un código estable que el front traduce a un mensaje claro.
// Documentado en docs/backend/shared.md

namespace App\Modules\Shared\Domain\Exceptions;

abstract class DomainException extends \DomainException
{
    /**
     * @param  array<string, scalar>  $params  Datos que el mensaje necesita, p. ej. ['max' => 150].
     */
    public function __construct(
        private readonly string $errorCode,
        private readonly array $params = [],
    ) {
        parent::__construct($errorCode);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, scalar>
     */
    public function params(): array
    {
        return $this->params;
    }
}
