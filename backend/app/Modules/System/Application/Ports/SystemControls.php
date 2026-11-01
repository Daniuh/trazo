<?php

declare(strict_types=1);

// Lo que la app puede hacer sobre sí misma sin tocar Docker: pedir a workers y Reverb que se reinicien (terminan
// limpios y Docker los levanta) y limpiar la caché de datos. Documentado en docs/backend/system.md

namespace App\Modules\System\Application\Ports;

interface SystemControls
{
    public function restartWorkers(): void;

    public function restartRealtime(): void;

    // Solo los datos calculados (cifras del dashboard); nunca contadores de seguridad ni sesiones
    public function clearDataCache(): void;
}
