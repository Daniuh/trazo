<?php

declare(strict_types=1);

// Roles y permisos tal como están guardados. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application\Ports;

use App\Modules\Users\Application\Data\PermissionInfo;
use App\Modules\Users\Application\Data\RoleInfo;

interface RoleCatalog
{
    /**
     * Ordenados de mayor a menor nivel.
     *
     * @return list<RoleInfo>
     */
    public function roles(): array;

    public function find(string $code): ?RoleInfo;

    /**
     * @return list<string>
     */
    public function permissionsOf(int $roleId): array;

    /**
     * @return list<PermissionInfo>
     */
    public function permissions(): array;
}
