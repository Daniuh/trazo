<?php

declare(strict_types=1);

// Caso de uso: la auditoría de administración, con el alcance de la jerarquía. Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Application;

use App\Modules\Audit\Application\Data\AdminAuditCriteria;
use App\Modules\Audit\Application\Data\AdminAuditEntry;
use App\Modules\Audit\Application\Data\AuditPage;
use App\Modules\Audit\Application\Ports\AdminAuditQuery;
use App\Modules\Audit\Domain\AuditAccess;
use App\Modules\Shared\Domain\Exceptions\ForbiddenException;
use App\Modules\Users\Domain\Administrator;

final readonly class ListAdminAudit
{
    public function __construct(
        private AuditAccess $access,
        private AdminAuditQuery $query,
    ) {}

    /**
     * @return AuditPage<AdminAuditEntry>
     */
    public function handle(Administrator $viewer, AdminAuditCriteria $criteria): AuditPage
    {
        if (! $this->access->canViewAdmin($viewer)) {
            throw new ForbiddenException;
        }

        return $this->query->search($criteria, $this->access->adminScope($viewer));
    }
}
