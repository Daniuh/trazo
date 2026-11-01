<?php

declare(strict_types=1);

// Una persona como aparece en la auditoría: su ULID público y su nombre. Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Application\Data;

final readonly class PersonRef
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}
}
