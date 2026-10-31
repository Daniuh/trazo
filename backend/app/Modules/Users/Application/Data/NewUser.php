<?php

declare(strict_types=1);

// Datos para crear una persona. No lleva contraseña: la crea ella misma desde el correo de bienvenida.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Application\Data;

use App\Modules\Users\Domain\PersonName;

final readonly class NewUser
{
    public function __construct(
        public PersonName $name,
        public string $email,
        public string $role,
    ) {}
}
