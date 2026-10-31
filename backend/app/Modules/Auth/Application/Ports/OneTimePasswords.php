<?php

declare(strict_types=1);

// Códigos de un solo uso basados en tiempo (TOTP, RFC 6238). La implementación es una librería; el resto del
// módulo no la conoce. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Ports;

interface OneTimePasswords
{
    public function newSecret(): string;

    // Dirección otpauth:// que la aplicación de autenticación lee desde el QR
    public function provisioningUri(string $account, string $secret): string;

    /**
     * Paso de tiempo del código si es válido y posterior a $afterStep; null si no lo es.
     */
    public function verify(string $secret, string $code, ?int $afterStep = null): ?int;
}
