<?php

declare(strict_types=1);

// Una cuenta, con lo justo para recuperar o cambiar su contraseña. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Password;

final readonly class Account
{
    public function __construct(
        public int $id,
        public string $email,
        public string $name,
    ) {}
}
