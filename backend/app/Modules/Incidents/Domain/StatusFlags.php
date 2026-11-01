<?php

declare(strict_types=1);

// Un estado del catálogo y lo que significa para el ciclo de vida (inicial, pendiente, final).
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain;

final readonly class StatusFlags
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public bool $isInitial,
        public bool $isPending,
        public bool $isFinal,
        // Llegar a este estado exige una nota (cómo se resolvió, por qué se cancela)
        public bool $requiresNote = false,
    ) {}

    /**
     * Forma que se guarda en el historial: legible aunque el estado cambie de nombre después.
     *
     * @return array{code: string, name: string}
     */
    public function snapshot(): array
    {
        return ['code' => $this->code, 'name' => $this->name];
    }
}
