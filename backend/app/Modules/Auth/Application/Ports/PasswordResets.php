<?php

declare(strict_types=1);

// Solicitudes de recuperación de contraseña. Los códigos y pases llegan en claro y se guardan solo con HMAC.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Ports;

use App\Modules\Auth\Application\Password\PasswordReset;
use DateTimeImmutable;

interface PasswordResets
{
    // Crea una solicitud nueva y anula las anteriores de la persona
    public function start(int $userId, string $code, DateTimeImmutable $expiresAt): void;

    // La solicitud vigente: sin usar ni anular (puede estar vencida; eso lo decide el caso de uso)
    public function current(int $userId): ?PasswordReset;

    public function matchesCode(PasswordReset $reset, string $code): bool;

    // Suma un intento fallido y devuelve cuántos lleva
    public function recordFailure(PasswordReset $reset): int;

    public function issueTicket(PasswordReset $reset, string $ticket, DateTimeImmutable $expiresAt): void;

    public function matchesTicket(PasswordReset $reset, string $ticket): bool;

    // Usada o anulada: ya no sirve para nada
    public function close(PasswordReset $reset): void;
}
