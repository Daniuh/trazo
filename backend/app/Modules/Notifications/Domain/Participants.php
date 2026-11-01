<?php

declare(strict_types=1);

// Las personas relacionadas con un incidente en el momento de un cambio: quién lo creó, a quién está asignado, quiénes
// escribieron en su conversación y quién pidió la reapertura que se acaba de decidir. Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Domain;

final readonly class Participants
{
    /**
     * @param  list<int>  $commenters
     */
    public function __construct(
        public int $creator,
        public ?int $assignee,
        public array $commenters = [],
        public ?int $reopenRequester = null,
        // Si el incidente es de la prioridad más alta (Crítica), para el aviso de coordinación al crearlo
        public bool $critical = false,
    ) {}
}
