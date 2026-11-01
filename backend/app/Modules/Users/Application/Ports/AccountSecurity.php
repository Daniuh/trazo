<?php

declare(strict_types=1);

// Lo que la administración le pide al módulo de sesión: cerrar las sesiones de una persona, quitarle el MFA y
// confirmar que quien administra es quien dice ser (con el código de su aplicación). Documentado en docs/backend/users.md

namespace App\Modules\Users\Application\Ports;

use App\Modules\Shared\Domain\Exceptions\DomainException;

interface AccountSecurity
{
    public function revokeSessions(int $userId): void;

    public function removeMfa(int $userId): void;

    /**
     * Reautenticación: el código actual de la aplicación de quien administra.
     *
     * @throws DomainException si falta, no es correcto, no tiene MFA o hizo demasiados intentos
     */
    public function confirmIdentity(int $adminId, ?string $code): void;
}
