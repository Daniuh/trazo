<?php

declare(strict_types=1);

// Quién usa la pantalla de sistema, con sus permisos efectivos. Las reglas preguntan por permisos, nunca por roles.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Domain;

final readonly class Operator
{
    public const HEALTH = 'system.health.view';

    public const LOGS = 'system.logs.view';

    public const ACTIONS = 'system.actions.execute';

    /**
     * @param  list<string>  $permissions
     */
    public function __construct(
        public int $id,
        private array $permissions,
    ) {}

    public function can(string $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }
}
