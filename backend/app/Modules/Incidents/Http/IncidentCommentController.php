<?php

declare(strict_types=1);

// Endpoints de la conversación de un incidente: leerla de a páginas, escribir y borrar un mensaje propio reciente.
// Cada mensaje trae "mine" y sus "actions", así el front no calcula reglas. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http;

use App\Modules\Incidents\Application\Data\CommentRecord;
use App\Modules\Incidents\Application\IncidentConversation;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Http\Requests\CommentRequest;
use Illuminate\Http\JsonResponse;

final readonly class IncidentCommentController
{
    public function __construct(
        private RequestActor $requests,
        private IncidentConversation $conversation,
    ) {}

    public function index(CommentRequest $request, string $incident): JsonResponse
    {
        $actor = $this->requests->actor($request);
        $page = $this->conversation->read($actor, $incident, $request->before());
        $items = $page['items'];
        $last = end($items);

        return new JsonResponse([
            'data' => array_map(fn (CommentRecord $comment): array => $this->comment($comment, $actor), $items),
            'meta' => [
                'total' => $page['total'],
                // Con una página completa puede haber mensajes más viejos: se piden con before=<este id>
                'before' => count($items) === IncidentConversation::PAGE_SIZE && $last !== false ? $last->ulid : null,
            ],
        ]);
    }

    public function store(CommentRequest $request, string $incident): JsonResponse
    {
        $actor = $this->requests->actor($request);

        return new JsonResponse(['data' => $this->comment($this->conversation->write($actor, $incident, $request->body()), $actor)], 201);
    }

    public function destroy(CommentRequest $request, string $incident, string $comment): JsonResponse
    {
        $actor = $this->requests->actor($request);

        return new JsonResponse(['data' => $this->comment($this->conversation->delete($actor, $incident, $comment), $actor)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function comment(CommentRecord $comment, Actor $actor): array
    {
        return [
            'id' => $comment->ulid,
            'author' => ['id' => $comment->author->ulid, 'name' => $comment->author->name],
            'body' => $comment->body,
            'mine' => $comment->author->id === $actor->id,
            'created_at' => $comment->createdAt->format(DATE_ATOM),
            'deleted_at' => $comment->deletedAt?->format(DATE_ATOM),
            'actions' => ['delete' => $this->conversation->canDelete($actor, $comment)],
        ];
    }
}
