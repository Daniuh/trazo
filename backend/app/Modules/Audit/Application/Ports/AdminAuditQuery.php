<?php

declare(strict_types=1);

// Lectura del registro de administración dentro del alcance de quien mira, de lo más reciente a lo más antiguo.
// Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Application\Ports;

use App\Modules\Audit\Application\Data\AdminAuditCriteria;
use App\Modules\Audit\Application\Data\AdminAuditEntry;
use App\Modules\Audit\Application\Data\AuditPage;
use App\Modules\Audit\Domain\AdminAuditScope;

interface AdminAuditQuery
{
    /**
     * @return AuditPage<AdminAuditEntry>
     */
    public function search(AdminAuditCriteria $criteria, AdminAuditScope $scope): AuditPage;
}
