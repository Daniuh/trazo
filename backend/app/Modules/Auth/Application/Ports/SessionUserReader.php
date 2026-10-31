<?php

declare(strict_types=1);

// Lectura del usuario de la sesión con sus roles y permisos, en una sola consulta optimizada.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Ports;

use App\Modules\Auth\Application\SessionUser;

interface SessionUserReader
{
    public function find(int $userId): SessionUser;
}
