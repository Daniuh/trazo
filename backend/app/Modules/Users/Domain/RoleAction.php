<?php

declare(strict_types=1);

// Lo que se puede hacer con un rol existente. El valor es la clave del campo "actions" de la API.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Domain;

enum RoleAction: string
{
    case Update = 'update';
    case Delete = 'delete';
}
