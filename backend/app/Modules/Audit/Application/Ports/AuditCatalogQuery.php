<?php

declare(strict_types=1);

// Catálogos para los filtros de la auditoría: tipos de cada registro, roles, permisos y personas.
// Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Application\Ports;

use App\Modules\Audit\Application\Data\PersonRef;

interface AuditCatalogQuery
{
    /**
     * @return list<array{code: string, name: string}>
     */
    public function incidentTypes(): array;

    /**
     * @return list<array{code: string, name: string}>
     */
    public function adminTypes(): array;

    /**
     * @return list<array{code: string, name: string}>
     */
    public function roles(): array;

    /**
     * @return list<array{code: string, name: string}>
     */
    public function permissions(): array;

    /**
     * Todas las personas, activas o no, por nombre: cualquiera pudo hacer un cambio.
     *
     * @return list<PersonRef>
     */
    public function people(): array;
}
