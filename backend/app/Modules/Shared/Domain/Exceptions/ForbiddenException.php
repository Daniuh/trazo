<?php

declare(strict_types=1);

// La persona puede ver el recurso, pero sus permisos no incluyen esa acción.
// Documentado en docs/backend/shared.md

namespace App\Modules\Shared\Domain\Exceptions;

class ForbiddenException extends DomainException
{
    // Un módulo puede dar un motivo más preciso, por ejemplo que la persona está por encima en la jerarquía
    public function __construct(string $errorCode = 'FORBIDDEN_ACTION')
    {
        parent::__construct($errorCode);
    }
}
