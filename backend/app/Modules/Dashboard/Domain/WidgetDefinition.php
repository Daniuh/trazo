<?php

declare(strict_types=1);

// Un widget del catálogo: qué es, qué permiso exige y entre qué tamaños puede estar en la grilla.
// Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Domain;

use App\Modules\Incidents\Domain\Actor;

final readonly class WidgetDefinition
{
    public function __construct(
        public WidgetCode $code,
        public string $name,
        public string $description,
        public WidgetKind $kind,
        public ?string $permission,
        public int $minW,
        public int $maxW,
        public int $minH,
        public int $maxH,
        public int $defaultW,
        public int $defaultH,
        // Forma parte del diseño original; si no, se ofrece en "Agregar widget" y aparece oculto
        public bool $inDefaultLayout = true,
    ) {}

    // Sin permiso propio, lo ve todo el que entra al dashboard
    public function allows(Actor $actor): bool
    {
        return $this->permission === null || $actor->can($this->permission);
    }

    public function fits(int $w, int $h): bool
    {
        return $w >= $this->minW && $w <= $this->maxW && $h >= $this->minH && $h <= $this->maxH;
    }
}
