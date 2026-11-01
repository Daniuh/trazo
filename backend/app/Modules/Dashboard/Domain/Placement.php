<?php

declare(strict_types=1);

// Lugar de un widget en la grilla: columna y fila de inicio, ancho y alto (en celdas).
// Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Domain;

final readonly class Placement
{
    public function __construct(
        public int $x,
        public int $y,
        public int $w,
        public int $h,
    ) {}

    public function overlaps(self $other): bool
    {
        return $this->x < $other->x + $other->w
            && $other->x < $this->x + $this->w
            && $this->y < $other->y + $other->h
            && $other->y < $this->y + $this->h;
    }

    public function bottom(): int
    {
        return $this->y + $this->h;
    }
}
