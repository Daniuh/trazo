<?php

declare(strict_types=1);

// Un grupo del listado con su total: la clave pública (la que ve el front), el valor en la base con el que se filtra
// (id de estado, prioridad o persona; null para "sin asignar" y para los grupos de vencimiento) y cuántos tiene.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

final readonly class IncidentGroupSlice
{
    public function __construct(
        public string $key,
        public ?int $value,
        public int $total,
    ) {}
}
