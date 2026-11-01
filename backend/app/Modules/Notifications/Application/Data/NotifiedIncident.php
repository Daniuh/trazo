<?php

declare(strict_types=1);

// El incidente de una notificación, con lo justo para mostrarla y llevar a él. Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Application\Data;

final readonly class NotifiedIncident
{
    public function __construct(
        public string $ulid,
        public string $code,
        public string $title,
    ) {}
}
