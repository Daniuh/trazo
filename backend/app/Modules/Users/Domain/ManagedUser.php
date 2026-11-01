<?php

declare(strict_types=1);

// Lo que las reglas necesitan saber de la persona administrada. Documentado en docs/backend/users.md

namespace App\Modules\Users\Domain;

final readonly class ManagedUser
{
    public function __construct(
        public int $id,
        public int $level,
        public bool $isActive,
        public bool $mfaEnabled,
    ) {}
}
