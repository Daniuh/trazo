<?php

declare(strict_types=1);

// Casos de uso de los eventos del sistema: listarlos, el catálogo de tipos para los filtros y cambiar su estado
// (revisado, resuelto o de nuevo abierto). Documentado en docs/backend/system.md

namespace App\Modules\System\Application;

use App\Modules\System\Application\Data\EventCriteria;
use App\Modules\System\Application\Data\EventPage;
use App\Modules\System\Application\Data\EventRecord;
use App\Modules\System\Application\Data\EventType;
use App\Modules\System\Application\Ports\SystemEventLog;
use App\Modules\System\Domain\EventStatus;
use App\Modules\System\Domain\Exceptions\SystemExceptions;
use App\Modules\System\Domain\Operator;

final readonly class ManageSystemEvents
{
    public function __construct(
        private SystemGuard $guard,
        private SystemEventLog $log,
    ) {}

    public function list(Operator $operator, EventCriteria $criteria): EventPage
    {
        $this->guard->require($operator, Operator::LOGS);

        return $this->log->page($criteria);
    }

    /**
     * @return list<EventType>
     */
    public function types(Operator $operator): array
    {
        $this->guard->require($operator, Operator::LOGS);

        return $this->log->types();
    }

    public function changeStatus(Operator $operator, int $id, EventStatus $status): EventRecord
    {
        $this->guard->require($operator, Operator::LOGS);
        $event = $this->log->find($id) ?? throw SystemExceptions::eventNotFound();

        // Pedir el mismo estado no cambia nada ni deja otra firma
        if ($event->status !== $status) {
            $this->log->changeStatus($id, $status, $operator->id);
        }

        return $this->log->find($id) ?? throw SystemExceptions::eventNotFound();
    }
}
