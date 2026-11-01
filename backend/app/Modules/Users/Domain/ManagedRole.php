<?php

declare(strict_types=1);

// Lo que las reglas necesitan saber de un rol: su nivel, si es de sistema y cuántas personas lo tienen.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Domain;

final readonly class ManagedRole
{
    public function __construct(
        public int $level,
        public bool $isSystem,
        public int $members,
    ) {}
}
