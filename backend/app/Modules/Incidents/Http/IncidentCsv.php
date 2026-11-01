<?php

declare(strict_types=1);

// Forma del archivo de exportación: columnas en español, fechas en la hora del negocio (con su desfase en el
// encabezado) y "Vencido" calculado con la misma regla de la API. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Http;

use App\Modules\Incidents\Application\Data\IncidentExport;
use App\Modules\Incidents\Application\Data\IncidentExportRow;
use App\Modules\Incidents\Domain\IncidentRules;
use App\Modules\Shared\Application\Ports\Clock;
use App\Modules\Shared\Http\CsvDownload;
use DateTimeImmutable;
use DateTimeZone;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class IncidentCsv
{
    private const DATE_FORMAT = 'Y-m-d H:i';

    private DateTimeZone $zone;

    public function __construct(
        private IncidentRules $rules,
        private Clock $clock,
        string $timezone,
    ) {
        $this->zone = new DateTimeZone($timezone);
    }

    public function download(IncidentExport $export): StreamedResponse
    {
        $now = $this->clock->now();
        $offset = 'UTC'.$now->setTimezone($this->zone)->format('P');

        return CsvDownload::response(
            'incidentes-'.$now->setTimezone($this->zone)->format('Y-m-d-Hi').'.csv',
            [
                'Código', 'Título', 'Estado', 'Prioridad', 'Creado por', 'Asignado a',
                "Creado ({$offset})", "Vencimiento ({$offset})", "Resuelto ({$offset})", 'Vencido',
            ],
            $this->rows($export, $now),
        );
    }

    /**
     * @return iterable<list<string>>
     */
    private function rows(IncidentExport $export, DateTimeImmutable $now): iterable
    {
        foreach ($export->rows as $row) {
            yield $this->cells($row, $now);
        }
    }

    /**
     * @return list<string>
     */
    private function cells(IncidentExportRow $row, DateTimeImmutable $now): array
    {
        return [
            $row->code,
            $row->title,
            $row->status->name,
            $row->priority->name,
            $row->creator,
            $row->assignee ?? 'Sin asignar',
            $this->date($row->createdAt),
            $this->date($row->dueDate),
            $row->resolvedAt === null ? '' : $this->date($row->resolvedAt),
            $this->rules->isOverdue($row->status, $row->dueDate, $now) ? 'Sí' : 'No',
        ];
    }

    private function date(DateTimeImmutable $value): string
    {
        return $value->setTimezone($this->zone)->format(self::DATE_FORMAT);
    }
}
