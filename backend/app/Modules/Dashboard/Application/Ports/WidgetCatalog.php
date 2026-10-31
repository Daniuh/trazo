<?php

declare(strict_types=1);

// Catálogo de widgets activos, en el orden en que aparecen en el diseño original. Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application\Ports;

use App\Modules\Dashboard\Domain\WidgetDefinition;

interface WidgetCatalog
{
    /**
     * @return list<WidgetDefinition>
     */
    public function active(): array;
}
