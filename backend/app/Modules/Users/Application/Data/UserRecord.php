<?php

declare(strict_types=1);

// Una persona como la ve la administración. Su nivel es el más alto de sus roles (o ninguno si no tiene).
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Application\Data;

use App\Modules\Users\Domain\ManagedUser;
use App\Modules\Users\Domain\RoleLevel;
use DateTimeImmutable;

final readonly class UserRecord
{
    /**
     * @param  list<RoleInfo>  $roles
     */
    public function __construct(
        public int $id,
        public string $ulid,
        // Nombre completo para mostrar (nombres + apellidos)
        public string $name,
        public string $givenNames,
        public string $familyNames,
        public string $email,
        public bool $isActive,
        public array $roles,
        public bool $mfaEnabled,
        public ?DateTimeImmutable $lastLoginAt,
        public DateTimeImmutable $createdAt,
    ) {}

    public function level(): int
    {
        return array_reduce($this->roles, fn (int $max, RoleInfo $role): int => max($max, $role->level), RoleLevel::NONE);
    }

    // El rol principal: el de más nivel. Hoy cada persona tiene uno
    public function role(): ?RoleInfo
    {
        $roles = $this->roles;
        usort($roles, fn (RoleInfo $a, RoleInfo $b): int => $b->level <=> $a->level);

        return $roles[0] ?? null;
    }

    public function managed(): ManagedUser
    {
        return new ManagedUser($this->id, $this->level(), $this->isActive, $this->mfaEnabled);
    }
}
