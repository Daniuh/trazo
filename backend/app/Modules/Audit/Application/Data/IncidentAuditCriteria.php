<?php

declare(strict_types=1);

// Qué parte del historial de incidentes se pide: rango, quién hizo el cambio, tipos, incidente y desde dónde seguir.
// Las personas llegan por ULID, los tipos por código y el incidente por su código TZ-26-0142.
// Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Application\Data;

use App\Modules\Audit\Domain\AuditPeriod;
use App\Modules\Shared\Domain\TimeCursor;

final readonly class IncidentAuditCriteria
{
    /**
     * @param  list<string>  $types
     */
    public function __construct(
        public AuditPeriod $period,
        public ?string $actor,
        public array $types,
        public ?string $incident,
        public ?TimeCursor $cursor,
        public int $perPage,
    ) {}
}
