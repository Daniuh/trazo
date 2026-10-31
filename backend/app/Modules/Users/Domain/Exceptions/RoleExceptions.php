<?php

declare(strict_types=1);

// Errores de negocio de la edición de roles. Cada uno lleva su código estable para el catálogo de mensajes del front.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\ConflictException;
use App\Modules\Shared\Domain\Exceptions\DomainException;
use App\Modules\Shared\Domain\Exceptions\ForbiddenException;
use App\Modules\Shared\Domain\Exceptions\ResourceNotFoundException;
use App\Modules\Shared\Domain\Exceptions\RuleViolationException;
use App\Modules\Users\Domain\RoleDenial;

final class RoleExceptions
{
    public static function denied(RoleDenial $denial): DomainException
    {
        return $denial->isPermission() ? new ForbiddenException($denial->value) : new ConflictException($denial->value);
    }

    public static function notFound(): ResourceNotFoundException
    {
        return new ResourceNotFoundException('RESOURCE_NOT_FOUND');
    }

    // El nivel pedido está en el de quien administra o por encima (o es la cima)
    public static function levelNotAllowed(int $max): RuleViolationException
    {
        return new RuleViolationException('ROLE_LEVEL_NOT_ALLOWED', ['max' => $max]);
    }
}
