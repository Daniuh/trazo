<?php

declare(strict_types=1);

// Un mensaje de la conversación de un incidente. Si se borró, el texto viene vacío y queda la fecha del borrado.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Data;

use DateTimeImmutable;

final readonly class CommentRecord
{
    public function __construct(
        public int $id,
        public string $ulid,
        public int $incidentId,
        public Person $author,
        public ?string $body,
        public DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $deletedAt,
    ) {}

    public function deleted(): bool
    {
        return $this->deletedAt !== null;
    }
}
