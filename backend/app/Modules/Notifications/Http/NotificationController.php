<?php

declare(strict_types=1);

// Endpoints del centro de notificaciones (/api/v1/notifications): leer las propias por pestaña, contar las sin leer y
// marcarlas como leídas. Cada respuesta que cambia algo devuelve las sin leer, para que la campana no pregunte otra
// vez. Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Http;

use App\Modules\Incidents\Http\RequestActor;
use App\Modules\Notifications\Application\Data\NotificationRecord;
use App\Modules\Notifications\Application\PersonalNotifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class NotificationController
{
    public function __construct(
        private RequestActor $requests,
        private PersonalNotifications $notifications,
    ) {}

    public function index(NotificationRequest $request): JsonResponse
    {
        $actor = $this->requests->actor($request);
        $items = $this->notifications->page($actor, $request->category(), $request->before());
        $last = end($items);

        return new JsonResponse([
            'data' => array_map($this->notification(...), $items),
            'meta' => [
                'unread' => $this->notifications->unread($actor->id),
                // Con una página completa puede haber más viejas: se piden con before=<esta>
                'before' => count($items) === PersonalNotifications::PAGE_SIZE && $last !== false ? $last->ulid : null,
            ],
        ]);
    }

    public function unread(Request $request): JsonResponse
    {
        return new JsonResponse(['data' => $this->notifications->unread($this->userId($request))]);
    }

    public function read(Request $request, string $notification): JsonResponse
    {
        return new JsonResponse(['data' => $this->notifications->markRead($this->userId($request), strtolower($notification))]);
    }

    public function readAll(NotificationRequest $request): JsonResponse
    {
        return new JsonResponse(['data' => $this->notifications->markAllRead(
            $this->userId($request),
            $request->category(),
            $request->incident(),
        )]);
    }

    /**
     * @return array<string, mixed>
     */
    private function notification(NotificationRecord $item): array
    {
        return [
            'id' => $item->ulid,
            'type' => $item->type->value,
            'category' => $item->type->category()->value,
            'occurrences' => $item->occurrences,
            'actor' => $item->actorName === null ? null : ['name' => $item->actorName],
            'incident' => $item->incident === null ? null : [
                'id' => $item->incident->ulid,
                'code' => $item->incident->code,
                'title' => $item->incident->title,
            ],
            'excerpt' => $item->excerpt,
            'status' => $item->status,
            'comment_deleted' => $item->commentDeleted,
            'unassigned' => $item->unassigned,
            'read' => $item->readAt !== null,
            'created_at' => $item->createdAt->format(DATE_ATOM),
        ];
    }

    // Estas rutas pasan por auth:sanctum, así que siempre hay usuario
    private function userId(Request $request): int
    {
        return (int) $request->user()?->getAuthIdentifier();
    }
}
