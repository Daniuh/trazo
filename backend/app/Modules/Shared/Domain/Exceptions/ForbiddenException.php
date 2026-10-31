<?php

declare(strict_types=1);

// La persona puede ver el recurso, pero sus permisos no incluyen esa acción.
// Documentado en docs/backend/shared.md

namespace App\Modules\Shared\Domain\Exceptions;

class ForbiddenException extends DomainException
{
    public function __construct()
    {
        parent::__construct('FORBIDDEN_ACTION');
    }
}
