<?php

declare(strict_types=1);

// Una notificación tal como la ve su destinatario. Si ya no puede ver el incidente, "incident" llega vacío y el aviso
// no muestra su código, su título ni el mensaje. Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Application\Data;

use App\Modules\Notifications\Domain\NotificationType;
use DateTimeImmutable;

final readonly class NotificationRecord
{
    public function __construct(
        public string $ulid,
        public NotificationType $type,
        public int $occurrences,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $readAt,
        public ?string $actorName,
        public ?NotifiedIncident $incident,
        // Texto del mensaje, del motivo o nombre del estado nuevo, según el tipo; vacío si no aplica o no se puede ver
        public ?string $excerpt,
        public ?string $status,
        public bool $commentDeleted,
        // En los avisos de coordinación: el incidente no tiene responsable
        public bool $unassigned = false,
    ) {}
}
