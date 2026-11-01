<?php

declare(strict_types=1);

// Quién borra un mensaje de la conversación y hasta cuándo. Documentado en docs/backend/incidents.md

use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\CommentRules;
use App\Modules\Incidents\Domain\Denial;

it('borra solo el propio, sin borrar, y dentro de los 5 minutos', function (): void {
    $rules = new CommentRules;
    $now = new DateTimeImmutable('2026-10-28 12:00:00');
    $author = new Actor(1, []);

    expect($rules->deleteDenial($author, 1, $now->modify('-5 minutes'), false, $now))->toBeNull()
        ->and($rules->deleteDenial($author, 1, $now->modify('-5 minutes -1 second'), false, $now))->toBe(Denial::CommentDeleteExpired)
        ->and($rules->deleteDenial(new Actor(2, []), 1, $now, false, $now))->toBe(Denial::CommentNotOwn)
        ->and($rules->deleteDenial($author, 1, $now, true, $now))->toBe(Denial::CommentDeleted);
});
