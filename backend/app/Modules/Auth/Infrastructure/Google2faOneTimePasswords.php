<?php

declare(strict_types=1);

// Códigos TOTP con pragmarx/google2fa: 6 dígitos cada 30 segundos, compatibles con cualquier aplicación de
// autenticación. Acepta el código anterior y el siguiente por si el reloj del teléfono está un poco corrido.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Infrastructure;

use App\Modules\Auth\Application\Ports\OneTimePasswords;
use PragmaRX\Google2FA\Google2FA;

final readonly class Google2faOneTimePasswords implements OneTimePasswords
{
    // Un paso antes y uno después del actual: hasta 30 segundos de diferencia de reloj
    private const WINDOW = 1;

    public function __construct(
        private Google2FA $google2fa,
        private string $issuer,
    ) {}

    public function newSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    public function provisioningUri(string $account, string $secret): string
    {
        return $this->google2fa->getQRCodeUrl($this->issuer, $account, $secret);
    }

    public function verify(string $secret, string $code, ?int $afterStep = null): ?int
    {
        if (preg_match('/^\d{6}$/', $code) !== 1) {
            return null;
        }

        // Con un paso de referencia, la librería devuelve el paso del código (y rechaza los anteriores o iguales)
        $step = $this->google2fa->verifyKeyNewer($secret, $code, $afterStep ?? 0, self::WINDOW);

        return is_int($step) ? $step : null;
    }
}
