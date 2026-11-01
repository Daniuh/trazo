<?php

declare(strict_types=1);

// Registro de eventos relevantes del sistema (fallas, infraestructura y seguridad), agrupados por huella. Lo usan
// todos los módulos; registrar nunca hace fallar lo que se estaba haciendo. Documentado en docs/backend/system.md

namespace App\Modules\Shared\Application\Ports;

use App\Modules\Shared\Application\SystemEventType;

interface SystemEvents
{
    /**
     * @param  string  $source  Qué lo produjo, dentro del tipo (una clase y línea, un servicio, una cuenta). Con el tipo arma la huella
     * @param  string  $message  Texto corto y sin datos sensibles
     * @param  array<string, scalar|null>  $context  Datos para entenderlo, sin secretos
     */
    public function record(SystemEventType $type, string $source, string $message, array $context = []): void;
}
