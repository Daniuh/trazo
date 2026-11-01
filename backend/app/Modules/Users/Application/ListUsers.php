<?php

declare(strict_types=1);

// Caso de uso: listar personas con búsqueda, filtros y página. Solo quien puede ver usuarios.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Application;

use App\Modules\Users\Application\Data\UserListCriteria;
use App\Modules\Users\Application\Data\UserPage;
use App\Modules\Users\Application\Ports\UserListQuery;
use App\Modules\Users\Domain\Administrator;

final readonly class ListUsers
{
    public function __construct(
        private UserGate $gate,
        private UserListQuery $query,
    ) {}

    public function handle(Administrator $admin, UserListCriteria $criteria): UserPage
    {
        $this->gate->ensureCanView($admin);

        return $this->query->search($criteria);
    }
}
