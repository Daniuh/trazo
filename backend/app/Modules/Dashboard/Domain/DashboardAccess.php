<?php

declare(strict_types=1);

// Quién entra al dashboard y con qué nombre lo ve. Los datos siempre siguen la visibilidad de incidentes;
// el permiso solo abre la página y decide si es el resumen general o el personal. Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Domain;

use App\Modules\Incidents\Domain\Actor;

final class DashboardAccess
{
    public static function canView(Actor $actor): bool
    {
        return $actor->can('dashboard.view.any') || $actor->can('dashboard.view.own');
    }

    public static function isGeneral(Actor $actor): bool
    {
        return $actor->can('dashboard.view.any');
    }
}
