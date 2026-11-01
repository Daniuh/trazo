<?php

declare(strict_types=1);

// Solo en desarrollo: emite un token de sesión para una cuenta de demostración sin pasar por el MFA. Lo usa el escaneo
// activo de ZAP, que no puede escribir códigos de un teléfono. En producción el comando no existe.
// Documentado en docs/seguridad/zap.md

namespace App\Modules\Auth\Infrastructure\Console;

use App\Modules\Auth\Application\Ports\SessionTokens;
use App\Modules\Auth\Application\Ports\UserAccounts;
use Illuminate\Console\Command;

final class DevTokenCommand extends Command
{
    protected $signature = 'trazo:dev-token {email : Correo de una cuenta activa}';

    protected $description = 'Emite un token de sesión para pruebas locales (solo en desarrollo)';

    public function handle(UserAccounts $accounts, SessionTokens $tokens): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('Este comando solo existe en desarrollo.');

            return self::FAILURE;
        }

        $account = $accounts->findActiveByEmail((string) $this->argument('email'));
        if ($account === null) {
            $this->error('No hay una cuenta activa con ese correo.');

            return self::FAILURE;
        }

        // Solo el token en la salida, para usarlo directo en un script
        $this->line($tokens->issue($account->id)->token);

        return self::SUCCESS;
    }
}
