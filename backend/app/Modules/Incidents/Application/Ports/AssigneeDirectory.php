<?php

declare(strict_types=1);

// Personas que pueden recibir incidentes: cuentas activas con permiso para atenderlos.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Ports;

use App\Modules\Incidents\Application\Data\Person;

interface AssigneeDirectory
{
    /** Null si no existe o no puede recibir incidentes. */
    public function find(string $ulid): ?Person;

    /**
     * @return list<Person>
     */
    public function all(): array;
}
