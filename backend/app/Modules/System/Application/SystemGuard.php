<?php

declare(strict_types=1);

// Las protecciones comunes de la pantalla de sistema: el permiso de lo que se pide y, para las acciones, la
// reautenticación con el código de la aplicación. La confirmación la pide el front; el límite de frecuencia, la ruta.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Application;

use App\Modules\Shared\Domain\Exceptions\ForbiddenException;
use App\Modules\System\Application\Ports\IdentityCheck;
use App\Modules\System\Domain\Operator;

final readonly class SystemGuard
{
    public function __construct(private IdentityCheck $identity) {}

    public function require(Operator $operator, string $permission): void
    {
        if (! $operator->can($permission)) {
            throw new ForbiddenException;
        }
    }

    // Una acción: el permiso primero, así quien no lo tiene no gasta intentos de código
    public function authorizeAction(Operator $operator, ?string $code): void
    {
        $this->require($operator, Operator::ACTIONS);
        $this->identity->confirm($operator->id, $code);
    }
}
