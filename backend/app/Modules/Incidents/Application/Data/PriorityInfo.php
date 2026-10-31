<?php

declare(strict_types=1);

// Una prioridad del catálogo con su tiempo de resolución sugerido.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Data;

final readonly class PriorityInfo
{
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public int $level,
        public int $resolutionMinutes,
    ) {}

    /**
     * @return array{code: string, name: string}
     */
    public function snapshot(): array
    {
        return ['code' => $this->code, 'name' => $this->name];
    }
}
