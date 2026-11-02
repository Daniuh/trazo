<?php

declare(strict_types=1);

// La versión del incidente que conocía quien hace el cambio (bloqueo optimista), común a las validaciones que la piden.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http\Requests;

trait ReadsVersion
{
    public function version(): int
    {
        return $this->integer('version');
    }
}
