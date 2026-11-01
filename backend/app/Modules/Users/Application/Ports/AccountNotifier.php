<?php

declare(strict_types=1);

// Avisos por correo a la persona administrada: bienvenida y cada cambio en su cuenta. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application\Ports;

use App\Modules\Users\Application\AccountEvent;

interface AccountNotifier
{
    // Cuenta nueva: cómo crear su contraseña e ingresar
    public function welcome(int $userId): void;

    /**
     * @param  array<string, string>  $params  Lo que el texto necesita, p. ej. el nombre del rol nuevo
     */
    public function notify(int $userId, AccountEvent $event, array $params = []): void;

    // El correo cambió: se avisa a la dirección anterior, por si no lo esperaba
    public function emailChanged(string $previousEmail, string $name): void;
}
