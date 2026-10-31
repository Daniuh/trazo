<?php

declare(strict_types=1);

// Reglas de un mensaje de la conversación: solo quien lo escribió lo borra, y solo durante unos minutos, para corregir
// un error sin cambiar lo que otros ya leyeron o respondieron. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain;

use DateTimeImmutable;

final class CommentRules
{
    public const DELETE_WINDOW_MINUTES = 5;

    public function deleteDenial(Actor $actor, int $authorId, DateTimeImmutable $writtenAt, bool $deleted, DateTimeImmutable $now): ?Denial
    {
        return match (true) {
            $deleted => Denial::CommentDeleted,
            $authorId !== $actor->id => Denial::CommentNotOwn,
            $writtenAt < $now->modify('-'.self::DELETE_WINDOW_MINUTES.' minutes') => Denial::CommentDeleteExpired,
            default => null,
        };
    }
}
