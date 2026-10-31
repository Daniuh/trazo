<?php

declare(strict_types=1);

// Una tanda de eventos y el cursor para pedir la siguiente (null si no hay más). Documentado en docs/backend/system.md

namespace App\Modules\System\Application\Data;

use App\Modules\Shared\Domain\TimeCursor;

final readonly class EventPage
{
    /**
     * @param  list<EventRecord>  $items
     */
    public function __construct(
        public array $items,
        public ?TimeCursor $next,
    ) {}
}
