<?php

declare(strict_types=1);

// Qué eventos se piden: estados, severidades y tipos (vacío = todos), cursor y tamaño de la tanda.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Application\Data;

use App\Modules\Shared\Domain\TimeCursor;

final readonly class EventCriteria
{
    /**
     * @param  list<string>  $statuses
     * @param  list<string>  $severities
     * @param  list<string>  $types
     */
    public function __construct(
        public array $statuses = [],
        public array $severities = [],
        public array $types = [],
        public ?TimeCursor $cursor = null,
        public int $perPage = 25,
    ) {}
}
