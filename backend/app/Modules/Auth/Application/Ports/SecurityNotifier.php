<?php

declare(strict_types=1);

// Avisa a la persona de un cambio en la seguridad de su cuenta, para que lo note si no fue ella.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Application\Ports;

use App\Modules\Auth\Application\Mfa\SecurityEvent;

interface SecurityNotifier
{
    public function notify(int $userId, SecurityEvent $event): void;
}
