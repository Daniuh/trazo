<?php

declare(strict_types=1);

// Un componente del panel de salud con su estado y los datos que lo explican. Documentado en docs/backend/system.md

namespace App\Modules\System\Application\Data;

use App\Modules\System\Domain\HealthStatus;

final readonly class ComponentHealth
{
    /**
     * @param  array<string, scalar|null>  $details
     */
    public function __construct(
        public string $code,
        public HealthStatus $status,
        public array $details,
    ) {}
}
