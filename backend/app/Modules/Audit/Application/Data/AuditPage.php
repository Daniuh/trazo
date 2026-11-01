<?php

declare(strict_types=1);

// Una tanda de la auditoría y el cursor para pedir la siguiente (null si no hay más). Lleva el rango que se aplicó,
// para que la pantalla muestre exactamente lo que se consultó. Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Application\Data;

use App\Modules\Audit\Domain\AuditCursor;
use App\Modules\Audit\Domain\AuditPeriod;

/**
 * @template T
 */
final readonly class AuditPage
{
    /**
     * @param  list<T>  $items
     */
    public function __construct(
        public array $items,
        public ?AuditCursor $next,
        public AuditPeriod $period,
    ) {}
}
