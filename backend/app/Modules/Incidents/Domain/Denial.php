<?php

declare(strict_types=1);

// Por qué no se puede hacer una acción. Cada motivo tiene su código y su mensaje propio en el front.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain;

enum Denial: string
{
    // No puede ni verlo: la API responde 404, como si no existiera
    case NotVisible = 'RESOURCE_NOT_FOUND';
    // Lo ve, pero su rol no incluye esa acción
    case Forbidden = 'FORBIDDEN_ACTION';
    case Finalized = 'INCIDENT_FINALIZED';
    case EditLocked = 'INCIDENT_EDIT_LOCKED';
    case AlreadyAssigned = 'INCIDENT_ALREADY_ASSIGNED';
    case NotDeleted = 'INCIDENT_NOT_DELETED';
}
