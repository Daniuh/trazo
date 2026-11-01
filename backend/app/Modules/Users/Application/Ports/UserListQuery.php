<?php

declare(strict_types=1);

// Listado de usuarios con búsqueda, filtros, orden y página. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application\Ports;

use App\Modules\Users\Application\Data\UserListCriteria;
use App\Modules\Users\Application\Data\UserPage;

interface UserListQuery
{
    public function search(UserListCriteria $criteria): UserPage;
}
