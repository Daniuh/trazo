<?php

declare(strict_types=1);

// Por qué no se puede hacer una acción con una persona. Cada motivo tiene su código y su mensaje en el front.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Domain;

enum UserDenial: string
{
    case Forbidden = 'FORBIDDEN_ACTION';
    // Su rol, sus permisos, su estado y su acceso se los cambia otra persona
    case Self = 'USER_SELF_MANAGEMENT';
    // Está en el mismo nivel o por encima de quien administra
    case Outranked = 'USER_OUTRANKED';
    // El Super admin tiene todos los permisos por su rol: no lleva excepciones
    case RootHasEverything = 'USER_ROOT_FULL_ACCESS';
    case AlreadyActive = 'USER_ALREADY_ACTIVE';
    case AlreadyInactive = 'USER_ALREADY_INACTIVE';
    case MfaNotEnabled = 'USER_MFA_NOT_ENABLED';

    // Los tres primeros son de permisos (403); el resto choca con el estado de la persona (409)
    public function isPermission(): bool
    {
        return in_array($this, [self::Forbidden, self::Self, self::Outranked], true);
    }
}
