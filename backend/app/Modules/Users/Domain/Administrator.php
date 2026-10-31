<?php

declare(strict_types=1);

// Quien administra: su id, su nivel en la jerarquía (el más alto de sus roles) y sus permisos efectivos.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Domain;

final readonly class Administrator
{
    /**
     * @param  list<string>  $permissions
     */
    public function __construct(
        public int $id,
        public int $level,
        private array $permissions,
    ) {}

    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    // La cima de la jerarquía administra a todos, incluso a sus pares; nunca a sí misma
    public function isRoot(): bool
    {
        return $this->level >= RoleLevel::ROOT;
    }

    // Solo se administra hacia abajo
    public function outranks(int $level): bool
    {
        return $this->isRoot() || $level < $this->level;
    }
}
