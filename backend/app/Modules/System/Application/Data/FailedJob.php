<?php

declare(strict_types=1);

// Un job fallido sin su contenido (puede traer datos sensibles, como un código enviado por correo): solo qué job era,
// en qué cola, cuándo falló y la primera línea del error. Documentado en docs/backend/system.md

namespace App\Modules\System\Application\Data;

use DateTimeImmutable;

final readonly class FailedJob
{
    public function __construct(
        public string $id,
        public string $job,
        public string $queue,
        public DateTimeImmutable $failedAt,
        public string $error,
    ) {}
}
