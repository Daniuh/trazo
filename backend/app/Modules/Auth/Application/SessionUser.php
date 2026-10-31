<?php

declare(strict_types=1);

// Quién tiene la sesión abierta y qué puede hacer. El front lo usa para armar el menú y las pantallas.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application;

final readonly class SessionUser
{
    /**
     * @param  list<array{code: string, name: string}>  $roles
     * @param  list<string>  $permissions
     */
    public function __construct(
        public string $ulid,
        public string $name,
        public string $email,
        public array $roles,
        public array $permissions,
    ) {}
}
