<?php

declare(strict_types=1);

// Lo que la pantalla de auditoría necesita para sus filtros y textos. Cada parte llega solo si quien mira tiene el
// permiso de esa pestaña. Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Application\Data;

final readonly class AuditCatalog
{
    /**
     * @param  list<array{code: string, name: string}>|null  $incidentTypes
     * @param  list<array{code: string, name: string}>|null  $adminTypes
     * @param  list<array{code: string, name: string}>  $roles
     * @param  list<array{code: string, name: string}>  $permissions
     * @param  list<PersonRef>  $people
     */
    public function __construct(
        public ?array $incidentTypes,
        public ?array $adminTypes,
        public array $roles,
        public array $permissions,
        public array $people,
    ) {}
}
