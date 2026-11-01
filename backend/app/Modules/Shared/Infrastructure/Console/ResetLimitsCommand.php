<?php

declare(strict_types=1);

// Solo en desarrollo: vacía los contadores de seguridad (límites de frecuencia y bloqueos del login), que viven en su
// propia base de Redis. Lo usa la suite E2E antes de empezar, para poder repetirla sin esperar 15 minutos.
// Nunca toca la caché de datos ni el modo mantenimiento. Documentado en docs/backend/shared.md

namespace App\Modules\Shared\Infrastructure\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Factory;

final class ResetLimitsCommand extends Command
{
    protected $signature = 'trazo:reset-limits';

    protected $description = 'Vacía los límites de frecuencia y los bloqueos (solo en desarrollo)';

    public function handle(Factory $cache): int
    {
        if (! app()->environment('local', 'testing')) {
            $this->error('Este comando solo existe en desarrollo.');

            return self::FAILURE;
        }

        $cache->store((string) config('cache.limiter'))->clear();
        $this->info('Límites de frecuencia y bloqueos vaciados.');

        return self::SUCCESS;
    }
}
