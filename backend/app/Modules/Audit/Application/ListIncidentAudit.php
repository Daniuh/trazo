<?php

declare(strict_types=1);

// Caso de uso: la auditoría de incidentes. Solo con su permiso. Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Application;

use App\Modules\Audit\Application\Data\AuditPage;
use App\Modules\Audit\Application\Data\IncidentAuditCriteria;
use App\Modules\Audit\Application\Data\IncidentAuditEntry;
use App\Modules\Audit\Application\Ports\IncidentAuditQuery;
use App\Modules\Audit\Domain\AuditAccess;
use App\Modules\Shared\Domain\Exceptions\ForbiddenException;
use App\Modules\Users\Domain\Administrator;

final readonly class ListIncidentAudit
{
    public function __construct(
        private AuditAccess $access,
        private IncidentAuditQuery $query,
    ) {}

    /**
     * @return AuditPage<IncidentAuditEntry>
     */
    public function handle(Administrator $viewer, IncidentAuditCriteria $criteria): AuditPage
    {
        if (! $this->access->canViewIncidents($viewer)) {
            throw new ForbiddenException;
        }

        return $this->query->search($criteria);
    }
}
