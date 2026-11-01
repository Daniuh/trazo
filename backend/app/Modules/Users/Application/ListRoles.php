<?php

declare(strict_types=1);

// Caso de uso: listar los roles con sus permisos, su política de MFA y cuántas personas tienen cada uno.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Application;

use App\Modules\Users\Application\Data\RoleDetail;
use App\Modules\Users\Application\Ports\RoleRepository;
use App\Modules\Users\Domain\Administrator;

final readonly class ListRoles
{
    public function __construct(
        private RoleGate $gate,
        private RoleRepository $roles,
    ) {}

    /**
     * @return list<RoleDetail>
     */
    public function handle(Administrator $admin): array
    {
        $this->gate->ensureCanView($admin);

        return $this->roles->all();
    }
}
