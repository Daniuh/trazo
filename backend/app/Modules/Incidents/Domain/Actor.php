<?php

declare(strict_types=1);

// Quién intenta hacer algo: su id y sus permisos granulares. Las reglas consultan permisos, nunca roles.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain;

final readonly class Actor
{
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
