<?php

declare(strict_types=1);

// Resultado de pedir una exportación: cuántas filas trae y las filas, que se leen recién al escribir el archivo.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Application\Data;

final readonly class IncidentExport
{
    /**
     * @param  iterable<IncidentExportRow>  $rows
     */
    public function __construct(
        public int $total,
        public iterable $rows,
    ) {}
}
