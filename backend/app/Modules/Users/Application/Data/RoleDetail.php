<?php

declare(strict_types=1);

// Un rol completo para la administración: sus datos, si es de sistema, sus permisos, su política de MFA y cuántas
// personas lo tienen. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application\Data;

use App\Modules\Users\Domain\ManagedRole;
use App\Modules\Users\Domain\MfaPolicy;

final readonly class RoleDetail
{
    /**
     * @param  list<string>  $permissions
     */
    public function __construct(
        public RoleInfo $role,
        public bool $isSystem,
        public array $permissions,
        public MfaPolicy $mfa,
        public int $members,
    ) {}

    public function managed(): ManagedRole
    {
        return new ManagedRole($this->role->level, $this->isSystem, $this->members);
    }
}
