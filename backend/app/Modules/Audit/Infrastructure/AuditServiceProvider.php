<?php

declare(strict_types=1);

// Conecta el módulo de auditoría con Laravel: implementaciones de los puertos. Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Infrastructure;

use App\Modules\Audit\Application\Ports\AdminAuditQuery;
use App\Modules\Audit\Application\Ports\AuditCatalogQuery;
use App\Modules\Audit\Application\Ports\IncidentAuditQuery;
use Illuminate\Support\ServiceProvider;

final class AuditServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        IncidentAuditQuery::class => DatabaseIncidentAudit::class,
        AdminAuditQuery::class => DatabaseAdminAudit::class,
        AuditCatalogQuery::class => DatabaseAuditCatalog::class,
    ];
}
