<?php

declare(strict_types=1);

// Modo mantenimiento en la caché compartida (Redis): lo ven al instante la API, los workers y el tiempo real. Se
// usa en lugar de "artisan down", que escribe un archivo en cada contenedor y deja pasar con una cookie.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Infrastructure;

use App\Modules\System\Application\Data\MaintenanceState;
use App\Modules\System\Application\Ports\MaintenanceSwitch;
use DateTimeImmutable;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CacheMaintenanceSwitch implements MaintenanceSwitch
{
    private const KEY = 'system:maintenance';

    public function __construct(private Repository $cache) {}

    // Si Redis no responde, se considera apagado: la app ya tiene problemas mayores y no conviene cerrarla del todo
    public function current(): ?MaintenanceState
    {
        try {
            $state = $this->cache->get(self::KEY);
        } catch (Throwable) {
            return null;
        }

        return is_array($state) && isset($state['since'], $state['by'], $state['name'])
            ? new MaintenanceState(new DateTimeImmutable((string) $state['since']), (int) $state['by'], (string) $state['name'])
            : null;
    }

    // El nombre se guarda junto al estado: el aviso lo muestra sin otra consulta
    public function enable(int $byUserId): void
    {
        $this->cache->forever(self::KEY, [
            'since' => now('UTC')->toIso8601String(),
            'by' => $byUserId,
            'name' => (string) DB::table('users')->where('id', $byUserId)->value('name'),
        ]);
    }

    public function disable(): void
    {
        $this->cache->forget(self::KEY);
    }
}
