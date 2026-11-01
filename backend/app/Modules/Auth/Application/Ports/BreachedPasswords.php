<?php

declare(strict_types=1);

// Si una contraseña aparece en filtraciones conocidas o entre las más comunes. Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Ports;

interface BreachedPasswords
{
    public function isBreached(string $password): bool;
}
