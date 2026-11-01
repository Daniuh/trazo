<?php

declare(strict_types=1);

// Un rol tal como lo necesitan los casos de uso: id interno, código, nombre y nivel en la jerarquía.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Application\Data;

final readonly class RoleInfo
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public int $level,
    ) {}
}
