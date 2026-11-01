<?php

declare(strict_types=1);

// Un incidente que acaba de vencer sin resolverse y todavía no tiene su aviso, con quien lo atiende.
// Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Application\Data;

final readonly class OverdueIncident
{
    public function __construct(
        public int $id,
        public ?int $assignee,
    ) {}
}
