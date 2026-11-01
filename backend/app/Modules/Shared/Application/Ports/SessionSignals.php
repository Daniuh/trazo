<?php

declare(strict_types=1);

// Aviso de que la sesión de unas personas cambió: rol, permisos, estado o sesiones cerradas. Cada pantalla abierta de
// esas personas vuelve a preguntar quién es; si su sesión ya no vale, se cierra al instante. El aviso sale cuando la
// transacción se confirma. Lo usan Users y Auth; lo implementa Realtime. Documentado en docs/backend/realtime.md

namespace App\Modules\Shared\Application\Ports;

interface SessionSignals
{
    public function changed(int ...$userIds): void;
}
