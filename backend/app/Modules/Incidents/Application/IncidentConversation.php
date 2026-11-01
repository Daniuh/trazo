<?php

declare(strict_types=1);

// Casos de uso de la conversación de un incidente: leerla, escribir y borrar un mensaje propio reciente.
// Leer exige poder ver el incidente; escribir, el permiso de comentar. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application;

use App\Modules\Incidents\Application\Data\CommentRecord;
use App\Modules\Incidents\Application\Data\IncidentRecord;
use App\Modules\Incidents\Application\Ports\IncidentComments;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\CommentRules;
use App\Modules\Incidents\Domain\Denial;
use App\Modules\Incidents\Domain\Exceptions\IncidentExceptions;
use App\Modules\Incidents\Domain\IncidentAction;
use App\Modules\Shared\Application\Ports\Clock;

final readonly class IncidentConversation
{
    public const PAGE_SIZE = 50;

    public function __construct(
        private IncidentGate $gate,
        private IncidentComments $comments,
        private CommentRules $rules,
        private Clock $clock,
    ) {}

    /**
     * @return array{incident: IncidentRecord, items: list<CommentRecord>, total: int}
     */
    public function read(Actor $actor, string $ulid, ?string $before): array
    {
        $incident = $this->gate->load($actor, $ulid, IncidentAction::View);

        return [
            'incident' => $incident,
            'items' => $this->comments->page($incident->id, $before, self::PAGE_SIZE),
            'total' => $this->comments->count($incident->id),
        ];
    }

    public function write(Actor $actor, string $ulid, string $body): CommentRecord
    {
        $incident = $this->gate->load($actor, $ulid, IncidentAction::Comment);

        return $this->comments->add($incident, $actor->id, $body);
    }

    public function delete(Actor $actor, string $ulid, string $commentUlid): CommentRecord
    {
        $incident = $this->gate->load($actor, $ulid, IncidentAction::View);
        $comment = $this->comments->find($incident->id, $commentUlid) ?? throw IncidentExceptions::denied(Denial::NotVisible);

        $denial = $this->rules->deleteDenial($actor, $comment->author->id, $comment->createdAt, $comment->deleted(), $this->clock->now());
        if ($denial !== null) {
            throw IncidentExceptions::denied($denial);
        }

        return $this->comments->markDeleted($incident, $comment);
    }

    // Si la persona todavía puede borrarlo (para las "actions" de cada mensaje)
    public function canDelete(Actor $actor, CommentRecord $comment): bool
    {
        return $this->rules->deleteDenial($actor, $comment->author->id, $comment->createdAt, $comment->deleted(), $this->clock->now()) === null;
    }
}
