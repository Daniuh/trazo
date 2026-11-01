<?php

declare(strict_types=1);

// El dashboard de una persona: si es el resumen general o el personal, si lo personalizó y sus widgets.
// Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application\Data;

final readonly class DashboardView
{
    /**
     * @param  list<WidgetView>  $widgets  Todos los que puede usar, visibles u ocultos
     */
    public function __construct(
        public bool $general,
        public bool $customized,
        public array $widgets,
    ) {}
}
