<?php

declare(strict_types=1);

// Caso de uso: datos de los widgets pedidos. Solo se calculan los que la persona puede usar, y siempre con
// la misma visibilidad del listado. Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application;

use App\Modules\Dashboard\Application\Data\MetricsContext;
use App\Modules\Dashboard\Application\Data\MetricsResult;
use App\Modules\Dashboard\Application\Metrics\WidgetMetrics;
use App\Modules\Dashboard\Application\Ports\WidgetCatalog;
use App\Modules\Dashboard\Domain\DashboardAccess;
use App\Modules\Dashboard\Domain\Exceptions\DashboardExceptions;
use App\Modules\Dashboard\Domain\WidgetCode;
use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\VisibilityScope;
use App\Modules\Shared\Application\Ports\Clock;
use App\Modules\Shared\Domain\Exceptions\ForbiddenException;

final readonly class ReadDashboardMetrics
{
    public function __construct(
        private WidgetCatalog $catalog,
        private WidgetMetrics $metrics,
        private Clock $clock,
    ) {}

    /**
     * @param  list<WidgetCode>  $codes
     */
    public function handle(Actor $actor, array $codes, int $trendDays): MetricsResult
    {
        if (! DashboardAccess::canView($actor)) {
            throw new ForbiddenException;
        }

        $allowed = [];
        foreach ($this->catalog->active() as $definition) {
            if ($definition->allows($actor)) {
                $allowed[$definition->code->value] = true;
            }
        }
        foreach ($codes as $code) {
            if (! isset($allowed[$code->value])) {
                throw DashboardExceptions::notAvailable($code->value);
            }
        }

        $context = new MetricsContext($actor, VisibilityScope::for($actor), $this->clock->now());
        $data = [];
        $oldest = $context->now;

        foreach ($codes as $code) {
            $metric = $this->metrics->build($code, $context, $trendDays);
            $data[$code->value] = $metric->value;
            $oldest = min($oldest, $metric->generatedAt);
        }

        return new MetricsResult($data, $oldest);
    }
}
