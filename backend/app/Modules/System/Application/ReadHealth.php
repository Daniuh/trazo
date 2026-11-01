<?php

declare(strict_types=1);

// Caso de uso: el panel de salud. Mide cada pieza en el momento y la interpreta con HealthRules.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Application;

use App\Modules\System\Application\Data\HealthReport;
use App\Modules\System\Application\Ports\HealthProbe;
use App\Modules\System\Application\Ports\MaintenanceSwitch;
use App\Modules\System\Domain\Operator;

final readonly class ReadHealth
{
    public function __construct(
        private SystemGuard $guard,
        private HealthProbe $probe,
        private MaintenanceSwitch $maintenance,
    ) {}

    public function handle(Operator $operator): HealthReport
    {
        $this->guard->require($operator, Operator::HEALTH);

        return HealthReport::from($this->probe->measure(), $this->maintenance->current());
    }
}
