<?php

declare(strict_types=1);

// Grupo de vencimiento de un incidente en el listado: vencido, vence hoy, en los próximos 7 días, más adelante o
// terminado. El orden de los casos es el orden de los grupos. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain;

enum DueBucket: string
{
    case Overdue = 'overdue';
    case Today = 'today';
    case Week = 'week';
    case Later = 'later';
    case Finished = 'finished';
}
