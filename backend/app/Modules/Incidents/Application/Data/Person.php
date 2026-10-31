<?php

declare(strict_types=1);

// Una persona tal como la muestra un incidente: quien lo reportó o quien lo atiende.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Data;

final readonly class Person
{
    public function __construct(
        public int $id,
        public string $ulid,
        public string $name,
    ) {}

    /**
     * Forma que se guarda en el historial: legible aunque la persona cambie de nombre después.
     *
     * @return array{id: string, name: string}
     */
    public function snapshot(): array
    {
        return ['id' => $this->ulid, 'name' => $this->name];
    }
}
