<?php

declare(strict_types=1);

// Reautenticación antes de una acción de sistema: el código actual de la aplicación de autenticación de quien la
// pide. Es la misma de la administración de usuarios. Documentado en docs/backend/system.md

namespace App\Modules\System\Application\Ports;

use App\Modules\Shared\Domain\Exceptions\DomainException;

interface IdentityCheck
{
    /**
     * @throws DomainException si falta, no es correcto, no tiene MFA o hizo demasiados intentos
     */
    public function confirm(int $userId, ?string $code): void;
}
