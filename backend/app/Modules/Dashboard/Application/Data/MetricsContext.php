<?php

declare(strict_types=1);

// Para quién se calculan las métricas: la persona, lo que puede ver y la clave de caché de ese alcance.
// Quienes ven todo comparten la caché; el resto tiene la suya. Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application\Data;

use App\Modules\Incidents\Domain\Actor;
use App\Modules\Incidents\Domain\VisibilityScope;
use DateTimeImmutable;

final readonly class MetricsContext
{
    public string $scopeKey;

    public function __construct(
        public Actor $actor,
        public VisibilityScope $scope,
        public DateTimeImmutable $now,
    ) {
        $this->scopeKey = $scope->all
            ? 'all'
            : sprintf('user-%d-%d%d%d', $actor->id, (int) $scope->own, (int) $scope->assigned, (int) $scope->unassigned);
    }
}
