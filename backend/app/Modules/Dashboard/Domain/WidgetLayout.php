<?php

declare(strict_types=1);

// Cómo tiene una persona un widget: dónde está y si se ve. Un widget oculto conserva su lugar.
// Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Domain;

final readonly class WidgetLayout
{
    public function __construct(
        public WidgetCode $code,
        public Placement $placement,
        public bool $visible,
    ) {}
}
