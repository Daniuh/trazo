<?php

declare(strict_types=1);

// Un widget tal como lo tiene la persona: su definición y su lugar. Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application\Data;

use App\Modules\Dashboard\Domain\WidgetDefinition;
use App\Modules\Dashboard\Domain\WidgetLayout;

final readonly class WidgetView
{
    public function __construct(
        public WidgetDefinition $definition,
        public WidgetLayout $layout,
    ) {}
}
