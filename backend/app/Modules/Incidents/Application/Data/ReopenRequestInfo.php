<?php

declare(strict_types=1);

// Solicitud de reapertura pendiente de un incidente: quién la pidió, por qué y cuándo.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Data;

use DateTimeImmutable;

final readonly class ReopenRequestInfo
{
    public function __construct(
        public Person $requester,
        public string $reason,
        public DateTimeImmutable $createdAt,
    ) {}
}
