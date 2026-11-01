<?php

declare(strict_types=1);

// Los jobs fallidos más recientes y cuántos hay en total. Documentado en docs/backend/system.md

namespace App\Modules\System\Application\Data;

final readonly class FailedJobList
{
    /**
     * @param  list<FailedJob>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
    ) {}
}
