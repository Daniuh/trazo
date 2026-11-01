<?php

declare(strict_types=1);

// Datos de los widgets pedidos y el momento del dato más antiguo entre ellos. Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application\Data;

use DateTimeImmutable;

final readonly class MetricsResult
{
    /**
     * @param  array<string, array<string, mixed>>  $data  Por código de widget
     */
    public function __construct(
        public array $data,
        public DateTimeImmutable $generatedAt,
    ) {}
}
