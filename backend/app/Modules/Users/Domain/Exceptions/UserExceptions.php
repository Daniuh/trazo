<?php

declare(strict_types=1);

// Errores de negocio de la administración de usuarios. Cada uno lleva su código estable para el catálogo de mensajes
// del front. Documentado en docs/backend/users.md

namespace App\Modules\Users\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\ConflictException;
use App\Modules\Shared\Domain\Exceptions\DomainException;
use App\Modules\Shared\Domain\Exceptions\ForbiddenException;
use App\Modules\Shared\Domain\Exceptions\ResourceNotFoundException;
use App\Modules\Shared\Domain\Exceptions\RuleViolationException;
use App\Modules\Users\Domain\UserDenial;

final class UserExceptions
{
    public static function denied(UserDenial $denial): DomainException
    {
        return $denial->isPermission() ? new ForbiddenException($denial->value) : new ConflictException($denial->value);
    }

    public static function notFound(): ResourceNotFoundException
    {
        return new ResourceNotFoundException('RESOURCE_NOT_FOUND');
    }

    // Ese rol está en su nivel o por encima: no lo puede dar
    public static function roleNotAssignable(): RuleViolationException
    {
        return new RuleViolationException('ROLE_NOT_ASSIGNABLE');
    }

    /**
     * Quiso conceder permisos que no tiene.
     *
     * @param  list<string>  $permissions
     */
    public static function permissionsNotGrantable(array $permissions): RuleViolationException
    {
        return new RuleViolationException('PERMISSION_NOT_GRANTABLE', ['permissions' => implode(', ', $permissions)]);
    }

    // El cambio dejaría el sistema sin ningún Super admin activo
    public static function lastRoot(): ConflictException
    {
        return new ConflictException('LAST_SUPER_ADMIN');
    }
}
