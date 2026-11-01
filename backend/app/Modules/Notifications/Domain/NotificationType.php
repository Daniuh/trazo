<?php

declare(strict_types=1);

// Tipos de notificación (códigos estables del catálogo notification_types) y la pestaña de cada uno. Los cuatro
// últimos son avisos de coordinación para quienes reparten el trabajo (ADR-0085).
// Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Domain;

enum NotificationType: string
{
    case CommentReceived = 'comment_received';
    case IncidentAssigned = 'incident_assigned';
    case StatusChanged = 'status_changed';
    case IncidentReopened = 'incident_reopened';
    case ReopenRejected = 'reopen_rejected';
    case UnassignedCreated = 'unassigned_created';
    case CriticalCreated = 'critical_created';
    case ReopenRequested = 'reopen_requested';
    case IncidentOverdue = 'incident_overdue';

    public function category(): NotificationCategory
    {
        return $this === self::CommentReceived ? NotificationCategory::Conversations : NotificationCategory::Activity;
    }
}
