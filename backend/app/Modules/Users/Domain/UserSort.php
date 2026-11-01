<?php

declare(strict_types=1);

// Órdenes permitidos en el listado de usuarios (lista blanca). Documentado en docs/backend/users.md

namespace App\Modules\Users\Domain;

enum UserSort: string
{
    case Name = 'name';
    case Email = 'email';
    case CreatedAt = 'created_at';
    case LastLoginAt = 'last_login_at';
}
