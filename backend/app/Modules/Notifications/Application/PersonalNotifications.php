<?php

declare(strict_types=1);

// Casos de uso del centro de notificaciones: leer las propias por pestaña, contar las sin leer, marcarlas como leídas
// y borrar las viejas. Cada persona solo ve y toca las suyas. Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Application;

use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\VisibilityScope;
use App\Modules\Notifications\Application\Data\NotificationRecord;
use App\Modules\Notifications\Application\Ports\NotificationSignals;
use App\Modules\Notifications\Application\Ports\NotificationStore;
use App\Modules\Notifications\Domain\NotificationCategory;
use App\Modules\Shared\Application\Ports\Clock;
use App\Modules\Shared\Domain\Exceptions\ResourceNotFoundException;

final readonly class PersonalNotifications
{
    public const PAGE_SIZE = 20;

    public function __construct(
        private NotificationStore $store,
        private NotificationSignals $signals,
        private Clock $clock,
        private int $retentionDays,
    ) {}

    /**
     * @return list<NotificationRecord>
     */
    public function page(Actor $actor, NotificationCategory $category, ?string $before): array
    {
        return $this->store->page($actor->id, $category, VisibilityScope::for($actor), $before, self::PAGE_SIZE);
    }

    /**
     * @return array<string, int>
     */
    public function unread(int $userId): array
    {
        return $this->store->unread($userId);
    }

    /**
     * @return array<string, int>
     */
    public function markRead(int $userId, string $ulid): array
    {
        if (! $this->store->markRead($userId, $ulid)) {
            throw new ResourceNotFoundException('NOTIFICATION_NOT_FOUND');
        }

        return $this->changed($userId);
    }

    /**
     * @return array<string, int>
     */
    public function markAllRead(int $userId, NotificationCategory $category, ?string $incidentUlid): array
    {
        $this->store->markAllRead($userId, $category, $incidentUlid);

        return $this->changed($userId);
    }

    public function prune(): int
    {
        return $this->store->prune($this->clock->now()->modify("-{$this->retentionDays} days"));
    }

    /**
     * Las demás pestañas abiertas de la persona también actualizan su campana.
     *
     * @return array<string, int>
     */
    private function changed(int $userId): array
    {
        $this->signals->changed([$userId]);

        return $this->store->unread($userId);
    }
}
