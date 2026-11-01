<?php

declare(strict_types=1);

// Dónde se guardan y se leen las notificaciones de cada persona. Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Application\Ports;

use App\Modules\Incidents\Domain\VisibilityScope;
use App\Modules\Notifications\Application\Data\NotificationRecord;
use App\Modules\Notifications\Domain\NotificationCategory;
use App\Modules\Notifications\Domain\NotificationType;
use DateTimeImmutable;

interface NotificationStore
{
    /**
     * Un mensaje nuevo para cada persona. Si ya tenía un aviso sin leer de ese incidente, lo reemplaza por uno más
     * reciente que suma el mensaje (así diez mensajes no son diez avisos).
     *
     * @param  list<int>  $users
     */
    public function addComment(array $users, int $incidentId, int $commentId): void;

    /**
     * @param  array<int, NotificationType>  $users  destinatario => tipo de aviso
     */
    public function addActivity(array $users, int $incidentId, int $activityId): void;

    /**
     * Un aviso sin mensaje ni registro del historial detrás (por ejemplo, un vencimiento), igual para todas.
     *
     * @param  list<int>  $users
     */
    public function addNotice(array $users, int $incidentId, NotificationType $type): void;

    /**
     * Página de la más nueva a la más vieja. Los incidentes fuera de $scope llegan sin datos.
     *
     * @return list<NotificationRecord>
     */
    public function page(int $userId, NotificationCategory $category, VisibilityScope $scope, ?string $before, int $limit): array;

    /**
     * Sin leer por pestaña.
     *
     * @return array<string, int>
     */
    public function unread(int $userId): array;

    /**
     * False si la notificación no es de esta persona.
     */
    public function markRead(int $userId, string $ulid): bool;

    /**
     * Marca como leídas las de una pestaña; con $incidentUlid, solo las de ese incidente.
     */
    public function markAllRead(int $userId, NotificationCategory $category, ?string $incidentUlid): void;

    /**
     * Borra las creadas antes de $before. Devuelve cuántas.
     */
    public function prune(DateTimeImmutable $before): int;
}
