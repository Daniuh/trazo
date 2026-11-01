<?php

declare(strict_types=1);

// Datos de un rol al crearlo o editarlo. El código solo se elige al crearlo: después no cambia, porque es su clave
// estable. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application\Data;

use App\Modules\Users\Domain\MfaPolicy;

final readonly class RoleDraft
{
    /**
     * @param  list<string>  $permissions
     */
    public function __construct(
        public string $name,
        public int $level,
        public array $permissions,
        public MfaPolicy $mfa,
    ) {}
}
