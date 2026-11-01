<?php

declare(strict_types=1);

// Por qué no se puede editar o eliminar un rol. Cada motivo tiene su código y su mensaje en el front.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Domain;

enum RoleDenial: string
{
    case Forbidden = 'FORBIDDEN_ACTION';
    // Está en el mismo nivel o por encima de quien administra
    case Outranked = 'ROLE_OUTRANKED';
    // El rol de la cima tiene siempre todos los permisos: no se edita ni se elimina
    case Locked = 'ROLE_LOCKED';
    // Uno de los roles base
    case System = 'ROLE_SYSTEM';
    // Alguna persona lo tiene
    case InUse = 'ROLE_IN_USE';

    // Los dos primeros son de permisos (403); el resto choca con el estado del rol (409)
    public function isPermission(): bool
    {
        return in_array($this, [self::Forbidden, self::Outranked], true);
    }
}
