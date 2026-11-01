<?php

declare(strict_types=1);

// Lectura del historial de todos los incidentes, incluidos los eliminados, de lo más reciente a lo más antiguo.
// Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Application\Ports;

use App\Modules\Audit\Application\Data\AuditPage;
use App\Modules\Audit\Application\Data\IncidentAuditCriteria;
use App\Modules\Audit\Application\Data\IncidentAuditEntry;

interface IncidentAuditQuery
{
    /**
     * @return AuditPage<IncidentAuditEntry>
     */
    public function search(IncidentAuditCriteria $criteria): AuditPage;
}
