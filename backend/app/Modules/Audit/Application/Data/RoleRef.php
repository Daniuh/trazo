<?php

declare(strict_types=1);

// Un rol como aparece en la auditoría. "deleted" indica que ya no existe y no hay ficha que abrir.
// Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Application\Data;

final readonly class RoleRef
{
    public function __construct(
        public string $code,
        public string $name,
        public bool $deleted = false,
    ) {}
}
