<?php

declare(strict_types=1);

// Cómo va un incidente con su plazo: su grupo de vencimiento, cuánto del plazo se ha gastado (solo pendientes) y si
// se resolvió a tiempo (solo terminados con fecha de resolución). Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain;

final readonly class Deadline
{
    public function __construct(
        public DueBucket $bucket,
        // De 0 a 100; null en los terminados
        public ?int $timeUsed,
        public ?bool $resolvedOnTime,
    ) {}
}
