<?php

declare(strict_types=1);

// Acciones posibles sobre un incidente. Sus valores son las claves de "actions" en la API.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain;

enum IncidentAction: string
{
    case View = 'view';
    case Update = 'update';
    case ChangeStatus = 'change_status';
    case Take = 'take';
    case Assign = 'assign';
    case Delete = 'delete';
    case Restore = 'restore';
    case RequestReopen = 'request_reopen';
    case Reopen = 'reopen';
    case DecideReopen = 'decide_reopen';
    case Comment = 'comment';
}
