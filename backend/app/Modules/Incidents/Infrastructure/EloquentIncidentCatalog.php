<?php

declare(strict_types=1);

// Catálogos de incidentes leídos una vez por petición: prioridades, estados y transiciones activas.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\Data\PriorityInfo;
use App\Modules\Incidents\Application\Ports\IncidentCatalog;
use App\Modules\Incidents\Domain\StatusFlags;
use App\Modules\Incidents\Infrastructure\Models\Priority;
use App\Modules\Incidents\Infrastructure\Models\Status;
use Illuminate\Support\Facades\DB;
use LogicException;

final class EloquentIncidentCatalog implements IncidentCatalog
{
    /** @var array<string, PriorityInfo>|null */
    private ?array $priorities = null;

    /** @var array<string, StatusFlags>|null */
    private ?array $statuses = null;

    /** @var array<int, list<StatusFlags>>|null */
    private ?array $transitions = null;

    public function __construct(private readonly IncidentMapper $mapper) {}

    /**
     * @return array<string, PriorityInfo>
     */
    public function priorities(): array
    {
        return $this->priorities ??= Priority::query()->where('is_active', true)->orderBy('level')->get()
            ->mapWithKeys(fn (Priority $priority): array => [$priority->code => $this->mapper->priority($priority)])
            ->all();
    }

    /**
     * @return array<string, StatusFlags>
     */
    public function statuses(): array
    {
        return $this->statuses ??= Status::query()->where('is_active', true)->orderBy('sort_order')->get()
            ->mapWithKeys(fn (Status $status): array => [$status->code => $this->mapper->status($status)])
            ->all();
    }

    public function priority(string $code): ?PriorityInfo
    {
        return $this->priorities()[$code] ?? null;
    }

    public function status(string $code): ?StatusFlags
    {
        return $this->statuses()[$code] ?? null;
    }

    public function initialStatus(): StatusFlags
    {
        foreach ($this->statuses() as $status) {
            if ($status->isInitial) {
                return $status;
            }
        }

        throw new LogicException('El catálogo de estados no tiene un estado inicial activo.');
    }

    public function nextStatuses(int $statusId): array
    {
        $this->transitions ??= $this->loadTransitions();

        return $this->transitions[$statusId] ?? [];
    }

    /**
     * @return array<int, list<StatusFlags>>
     */
    private function loadTransitions(): array
    {
        $allowed = [];
        foreach (DB::table('status_transitions')->get(['from_status_id', 'to_status_id']) as $row) {
            $allowed[(int) $row->from_status_id][(int) $row->to_status_id] = true;
        }

        // Se recorre el catálogo en orden, así el menú de estados se ve igual en todas partes
        $transitions = [];
        foreach ($this->statuses() as $from) {
            foreach ($this->statuses() as $to) {
                if (isset($allowed[$from->id][$to->id])) {
                    $transitions[$from->id][] = $to;
                }
            }
        }

        return $transitions;
    }
}
