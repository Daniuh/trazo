<?php

declare(strict_types=1);

// Lectura y escritura de roles con sus permisos y su política de MFA. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application\Ports;

use App\Modules\Users\Application\Data\RoleDetail;
use App\Modules\Users\Application\Data\RoleDraft;

interface RoleRepository
{
    /**
     * De mayor a menor nivel.
     *
     * @return list<RoleDetail>
     */
    public function all(): array;

    public function find(string $code): ?RoleDetail;

    // Devuelve el id del rol nuevo
    public function create(string $code, RoleDraft $draft): int;

    public function update(int $id, RoleDraft $draft): void;

    public function delete(int $id): void;

    // Quita las excepciones de sus personas que el rol vuelve innecesarias (concedido que ya trae, quitado que no trae)
    public function pruneMemberOverrides(int $id): void;
}
