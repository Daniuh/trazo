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
    // Solo se reabre lo que terminó (cerrado o cancelado)
    case NotFinalized = 'INCIDENT_NOT_FINALIZED';
    case ReopenPending = 'REOPEN_ALREADY_REQUESTED';
    case NoReopenRequest = 'REOPEN_NOT_REQUESTED';
    // Nadie aprueba su propia solicitud
    case OwnReopenRequest = 'REOPEN_OWN_REQUEST';
    // Mensajes de la conversación: solo el propio, durante unos minutos y una sola vez
    case CommentNotOwn = 'COMMENT_NOT_OWN';
    case CommentDeleteExpired = 'COMMENT_DELETE_EXPIRED';
    case CommentDeleted = 'COMMENT_ALREADY_DELETED';
}
