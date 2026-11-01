<?php

declare(strict_types=1);

// Caso de uso: los catálogos de la pantalla de auditoría, solo de las pestañas que quien mira puede ver.
// Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Application;

use App\Modules\Audit\Application\Data\AuditCatalog;
use App\Modules\Audit\Application\Ports\AuditCatalogQuery;
use App\Modules\Audit\Domain\AuditAccess;
use App\Modules\Shared\Domain\Exceptions\ForbiddenException;
use App\Modules\Users\Domain\Administrator;

final readonly class ReadAuditCatalog
{
    public function __construct(
        private AuditAccess $access,
        private AuditCatalogQuery $catalog,
    ) {}

    public function handle(Administrator $viewer): AuditCatalog
    {
        $incidents = $this->access->canViewIncidents($viewer);
        $admin = $this->access->canViewAdmin($viewer);
        if (! $incidents && ! $admin) {
            throw new ForbiddenException;
        }

        return new AuditCatalog(
            incidentTypes: $incidents ? $this->catalog->incidentTypes() : null,
            adminTypes: $admin ? $this->catalog->adminTypes() : null,
            // Los roles y permisos solo hacen falta para leer el detalle de la administración
            roles: $admin ? $this->catalog->roles() : [],
            permissions: $admin ? $this->catalog->permissions() : [],
            people: $this->catalog->people(),
        );
    }
}
