<?php

declare(strict_types=1);

// Niveles de la jerarquía de roles. El de cada rol es un dato (roles.level); aquí solo vive el tope, que es el del
// Super admin y ningún otro rol puede alcanzar. Documentado en docs/backend/users.md

namespace App\Modules\Users\Domain;

final class RoleLevel
{
    public const ROOT = 100;

    // Una persona sin rol queda abajo de todos
    public const NONE = 0;
}
