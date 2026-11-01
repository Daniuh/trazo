<?php

declare(strict_types=1);

// Acciones de sistema que se ejecutan con un botón. Todas piden permiso, confirmación y el código de la aplicación de
// autenticación, y quedan registradas. Mantenimiento y jobs fallidos tienen su propio caso de uso.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Domain;

enum SystemAction: string
{
    case RestartWorkers = 'restart-workers';
    case RestartRealtime = 'restart-realtime';
    case ClearCache = 'clear-cache';
}
