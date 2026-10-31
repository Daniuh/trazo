<?php

declare(strict_types=1);

// Ajustes del dashboard que vienen de la configuración: zona horaria del negocio (para contar días) y tamaños de listas.
// Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application\Data;

final readonly class DashboardSettings
{
    public function __construct(
        public string $timezone,
        public int $listLimit,
        public int $dueSoonHours,
        public int $workloadLimit,
    ) {}
}
