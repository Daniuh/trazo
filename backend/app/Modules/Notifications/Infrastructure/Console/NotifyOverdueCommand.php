<?php

declare(strict_types=1);

// Cada 5 minutos: avisa de los incidentes que acaban de vencer sin resolverse (ADR-0085).
// Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Infrastructure\Console;

use App\Modules\Notifications\Application\NotifyOverdue;
use Illuminate\Console\Command;

final class NotifyOverdueCommand extends Command
{
    protected $signature = 'notifications:overdue';

    protected $description = 'Avisa de los incidentes que acaban de vencer';

    public function handle(NotifyOverdue $notify): int
    {
        $this->info('Incidentes vencidos avisados: '.$notify->handle());

        return self::SUCCESS;
    }
}
