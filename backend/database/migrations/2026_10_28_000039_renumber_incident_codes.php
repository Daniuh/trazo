<?php

declare(strict_types=1);

// Pasa los códigos del formato anterior (INC-000123, sacado del id) al de año y consecutivo (TZ-26-0142): cada año se
// numera en el orden en que se crearon sus incidentes, incluidos los eliminados, y el contador queda al día.
// Ningún otro registro guarda el código: el historial y la auditoría lo leen siempre del incidente.
// Documentado en docs/decisiones/0074-codigo-de-incidente-por-ano.md

use App\Modules\Incidents\Infrastructure\IncidentCodes;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        IncidentCodes::fromConfig()->assignPending('INC-');
    }

    public function down(): void
    {
        // Sin vuelta atrás: el número anterior salía del id y ya no se usa en ningún lado
    }
};
