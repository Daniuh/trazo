<?php

declare(strict_types=1);

// Modo mantenimiento activo: desde cuándo y quién lo activó. Documentado en docs/backend/system.md

namespace App\Modules\System\Application\Data;

use DateTimeImmutable;

final readonly class MaintenanceState
{
    public function __construct(
        public DateTimeImmutable $since,
        public int $enabledBy,
        public string $enabledByName,
    ) {}
}
