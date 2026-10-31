<?php

declare(strict_types=1);

// Caso de uso: ejecutar una acción de sistema (reiniciar workers, reiniciar el tiempo real o limpiar la caché de
// datos) con permiso y reautenticación, y dejarla registrada en admin_actions. Documentado en docs/backend/system.md

namespace App\Modules\System\Application;

use App\Modules\Shared\Application\AdminActionType;
use App\Modules\Shared\Application\Data\RequestContext;
use App\Modules\Shared\Application\Ports\AdminLog;
use App\Modules\System\Application\Ports\SystemControls;
use App\Modules\System\Domain\Operator;
use App\Modules\System\Domain\SystemAction;

final readonly class RunSystemAction
{
    public function __construct(
        private SystemGuard $guard,
        private SystemControls $controls,
        private AdminLog $log,
    ) {}

    public function handle(Operator $operator, SystemAction $action, ?string $code, RequestContext $context): void
    {
        $this->guard->authorizeAction($operator, $code);

        [$run, $type] = match ($action) {
            SystemAction::RestartWorkers => [$this->controls->restartWorkers(...), AdminActionType::SystemWorkersRestarted],
            SystemAction::RestartRealtime => [$this->controls->restartRealtime(...), AdminActionType::SystemRealtimeRestarted],
            SystemAction::ClearCache => [$this->controls->clearDataCache(...), AdminActionType::SystemCacheCleared],
        };

        $run();
        $this->log->record($type, $operator->id, null, [], $context);
    }
}
