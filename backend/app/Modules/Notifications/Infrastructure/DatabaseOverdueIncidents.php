<?php

declare(strict_types=1);

// Incidentes que vencieron hace poco sin resolverse y aún no tienen su aviso. "Ya avisado" = hay un aviso de vencido
// del incidente creado después de su vencimiento: no hace falta marcar nada en la tabla de incidentes, y si la fecha
// se mueve y vuelve a vencer, avisa de nuevo. Usa el índice (status_id, due_date, deleted_at).
// Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Infrastructure;

use App\Modules\Notifications\Application\Data\OverdueIncident;
use App\Modules\Notifications\Application\Ports\OverdueIncidents;
use App\Modules\Notifications\Domain\NotificationType;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class DatabaseOverdueIncidents implements OverdueIncidents
{
    public function withoutNotice(DateTimeImmutable $since, DateTimeImmutable $now): array
    {
        $type = DB::table('notification_types')->where('code', NotificationType::IncidentOverdue->value)->value('id');

        return array_values(DB::table('incidents as i')
            ->join('statuses as s', 's.id', '=', 'i.status_id')
            ->where('s.is_pending', true)
            ->whereNull('i.deleted_at')
            ->where('i.due_date', '>', $this->utc($since))
            ->where('i.due_date', '<=', $this->utc($now))
            ->whereNotExists(fn (Builder $notice) => $notice->selectRaw('1')
                ->from('notifications as n')
                ->whereColumn('n.incident_id', 'i.id')
                ->where('n.notification_type_id', $type)
                ->whereColumn('n.created_at', '>=', 'i.due_date'))
            ->orderBy('i.due_date')
            ->get(['i.id', 'i.assigned_to'])
            ->map(fn (stdClass $row): OverdueIncident => new OverdueIncident(
                (int) $row->id,
                $row->assigned_to === null ? null : (int) $row->assigned_to,
            ))
            ->all());
    }

    // Las fechas se guardan en UTC
    private function utc(DateTimeImmutable $moment): string
    {
        return $moment->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
