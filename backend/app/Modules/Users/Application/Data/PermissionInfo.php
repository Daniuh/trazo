<?php

declare(strict_types=1);

// Un permiso con su nombre visible y el módulo al que pertenece (lo que va antes del primer punto).
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Application\Data;

final readonly class PermissionInfo
{
    public function __construct(
        public string $code,
        public string $name,
    ) {}

    public function module(): string
    {
        return explode('.', $this->code, 2)[0];
    }
}
