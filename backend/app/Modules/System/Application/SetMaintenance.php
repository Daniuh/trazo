<?php

declare(strict_types=1);

// Caso de uso: activar o desactivar el modo mantenimiento. Con él activo, todos reciben 503 menos quien puede
// ejecutar acciones de sistema. Pedir el estado que ya tiene es un conflicto, no se registra dos veces.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Application;

use App\Modules\Shared\Application\AdminActionType;
use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\AdminLog;
use App\Modules\System\Application\Ports\MaintenanceSwitch;
use App\Modules\System\Domain\Exceptions\SystemExceptions;
use App\Modules\System\Domain\Operator;

final readonly class SetMaintenance
{
    public function __construct(
        private SystemGuard $guard,
        private MaintenanceSwitch $maintenance,
        private AdminLog $log,
    ) {}

    public function handle(Operator $operator, bool $enabled, ?string $code, RequestContext $context): void
    {
        $this->guard->authorizeAction($operator, $code);
        $active = $this->maintenance->current() !== null;

        if ($enabled && $active) {
            throw SystemExceptions::maintenanceAlreadyEnabled();
        }
        if (! $enabled && ! $active) {
            throw SystemExceptions::maintenanceAlreadyDisabled();
        }

        if ($enabled) {
            $this->maintenance->enable($operator->id);
        } else {
            $this->maintenance->disable();
        }

        $type = $enabled ? AdminActionType::SystemMaintenanceEnabled : AdminActionType::SystemMaintenanceDisabled;
        $this->log->record($type, $operator->id, null, [], $context);
    }
}
