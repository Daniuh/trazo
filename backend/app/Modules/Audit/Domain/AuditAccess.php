<?php

declare(strict_types=1);

// Quién ve cada parte de la auditoría global. Los incidentes y la administración tienen permisos separados: quien
// supervisa no ve la administración de cuentas y quien administra no ve los incidentes de otros.
// Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Domain;

use App\Modules\Users\Domain\Administrator;

final class AuditAccess
{
    public const INCIDENTS = 'audit.incidents.view';

    public const ADMIN = 'audit.admin.view';

    public function canViewIncidents(Administrator $viewer): bool
    {
        return $viewer->can(self::INCIDENTS);
    }

    public function canViewAdmin(Administrator $viewer): bool
    {
        return $viewer->can(self::ADMIN);
    }

    // La cima ve toda la administración; los demás, lo que hicieron y lo que toca a quien está por debajo
    public function adminScope(Administrator $viewer): AdminAuditScope
    {
        return $viewer->isRoot() ? AdminAuditScope::everything() : AdminAuditScope::below($viewer->id, $viewer->level);
    }
}
