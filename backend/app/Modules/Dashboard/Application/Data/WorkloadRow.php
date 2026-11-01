<?php

declare(strict_types=1);

// Carga de una persona: cuántos pendientes tiene asignados y cuántos de ellos ya vencieron.
// Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application\Data;

final readonly class WorkloadRow
{
    public function __construct(
        public string $ulid,
        public string $name,
        public int $pending,
        public int $overdue,
    ) {}
}
