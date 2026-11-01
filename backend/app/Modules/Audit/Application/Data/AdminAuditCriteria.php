<?php

declare(strict_types=1);

// Qué parte del registro de administración se pide: rango, quién lo hizo, tipos, persona o rol afectado y desde dónde
// seguir. Las personas llegan por ULID y los roles y tipos por código. Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Application\Data;

use App\Modules\Audit\Domain\AuditPeriod;
use App\Modules\Shared\Domain\TimeCursor;

final readonly class AdminAuditCriteria
{
    /**
     * @param  list<string>  $types
     */
    public function __construct(
        public AuditPeriod $period,
        public ?string $actor,
        public array $types,
        public ?string $user,
        public ?string $role,
        public ?TimeCursor $cursor,
        public int $perPage,
    ) {}
}
