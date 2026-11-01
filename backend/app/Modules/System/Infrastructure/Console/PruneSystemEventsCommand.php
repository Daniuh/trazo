<?php

declare(strict_types=1);

// Limpieza diaria: borra los eventos resueltos hace más de lo que indica trazo.system.events_retention_days.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Infrastructure\Console;

use App\Modules\System\Application\CheckSystem;
use Illuminate\Console\Command;

final class PruneSystemEventsCommand extends Command
{
    protected $signature = 'system:prune-events';

    protected $description = 'Borra los eventos del sistema resueltos hace tiempo';

    public function handle(CheckSystem $check): int
    {
        $deleted = $check->prune(now()->toDateTimeImmutable());
        $this->info("Eventos borrados: {$deleted}");

        return self::SUCCESS;
    }
}
