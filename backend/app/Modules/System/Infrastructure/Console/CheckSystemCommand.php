<?php

declare(strict_types=1);

// Revisión de cada minuto que corre el scheduler: señales de vida y eventos por piezas caídas o cola acumulada.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Infrastructure\Console;

use App\Modules\System\Application\CheckSystem;
use Illuminate\Console\Command;

final class CheckSystemCommand extends Command
{
    protected $signature = 'system:check';

    protected $description = 'Revisa la salud del sistema y registra lo que falla';

    public function handle(CheckSystem $check): int
    {
        $check->run();

        return self::SUCCESS;
    }
}
