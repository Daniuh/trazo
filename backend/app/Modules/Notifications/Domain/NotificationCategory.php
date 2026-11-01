<?php

declare(strict_types=1);

// Pestañas del centro de notificaciones: mensajes de las conversaciones y lo demás que pasa en los incidentes.
// Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Domain;

enum NotificationCategory: string
{
    case Conversations = 'conversations';
    case Activity = 'activity';
}
