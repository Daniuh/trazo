<?php

declare(strict_types=1);

// Los permisos efectivos de una persona (rol + concedidos − quitados). Documentado en docs/backend/users.md

namespace App\Modules\Users\Application\Ports;

interface EffectivePermissions
{
    /**
     * @return list<string>
     */
    public function of(int $userId): array;

    // Después de cambiar su rol o sus permisos, para no responder con los de antes
    public function forget(int $userId): void;
}
