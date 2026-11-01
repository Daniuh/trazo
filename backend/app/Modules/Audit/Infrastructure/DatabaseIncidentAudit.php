<?php

declare(strict_types=1);

// Auditoría de incidentes en SQL: el historial de todos los incidentes (también los eliminados) con su tipo, su autor
// y el incidente en la misma consulta. Las personas, los tipos y el incidente se traducen a id antes de consultar,
// para que MySQL use el índice que corresponde: (user_id, created_at), (activity_type_id, created_at),
// (incident_id, created_at) o (created_at). Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Infrastructure;

use App\Modules\Audit\Application\Data\AuditPage;
use App\Modules\Audit\Application\Data\IncidentAuditCriteria;
use App\Modules\Audit\Application\Data\IncidentAuditEntry;
use App\Modules\Audit\Application\Data\PersonRef;
use App\Modules\Audit\Application\Ports\IncidentAuditQuery;
use App\Modules\Incidents\Domain\IncidentState;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class DatabaseIncidentAudit implements IncidentAuditQuery
{
    public function search(IncidentAuditCriteria $criteria): AuditPage
    {
        $query = DB::table('incident_activities as a')
            ->join('activity_types as t', 't.id', '=', 'a.activity_type_id')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->join('incidents as i', 'i.id', '=', 'a.incident_id')
            ->select([
                'a.id', 'a.created_at', 'a.changes', 'a.note', 'a.ip_address', 'a.user_agent',
                't.code as type_code', 't.name as type_name', 'u.ulid as actor_id', 'u.name as actor_name',
                'i.ulid as incident_id', 'i.code as incident_code', 'i.title as incident_title',
                'i.created_by', 'i.assigned_to', 'i.deleted_at',
            ]);

        if (! $this->filter($query, $criteria)) {
            return new AuditPage([], null, $criteria->period);
        }

        AuditWindow::apply($query, 'a', $criteria->period, $criteria->cursor, $criteria->perPage);
        [$rows, $next] = AuditWindow::split($query->get(), $criteria->perPage);

        return new AuditPage(array_map($this->entry(...), $rows), $next, $criteria->period);
    }

    // False si un filtro apunta a algo que no existe: entonces no hay nada que mostrar
    private function filter(Builder $query, IncidentAuditCriteria $criteria): bool
    {
        if ($criteria->actor !== null) {
            $actorId = DB::table('users')->where('ulid', $criteria->actor)->value('id');
            if ($actorId === null) {
                return false;
            }
            $query->where('a.user_id', $actorId);
        }

        if ($criteria->types !== []) {
            $typeIds = DB::table('activity_types')->whereIn('code', $criteria->types)->pluck('id')->all();
            if ($typeIds === []) {
                return false;
            }
            $query->whereIn('a.activity_type_id', $typeIds);
        }

        if ($criteria->incident !== null) {
            $incidentId = DB::table('incidents')->where('code', $criteria->incident)->value('id');
            if ($incidentId === null) {
                return false;
            }
            $query->where('a.incident_id', $incidentId);
        }

        return true;
    }

    private function entry(stdClass $row): IncidentAuditEntry
    {
        $changes = $row->changes === null ? null : json_decode((string) $row->changes, true, 512, JSON_THROW_ON_ERROR);

        return new IncidentAuditEntry(
            id: (int) $row->id,
            createdAt: AuditWindow::date((string) $row->created_at),
            typeCode: (string) $row->type_code,
            typeName: (string) $row->type_name,
            actor: new PersonRef((string) $row->actor_id, (string) $row->actor_name),
            incidentId: (string) $row->incident_id,
            incidentCode: (string) $row->incident_code,
            incidentTitle: (string) $row->incident_title,
            // El estado no cuenta para ver un incidente: solo quién lo creó, a quién está asignado y si se eliminó
            incident: new IncidentState(
                (int) $row->created_by,
                $row->assigned_to === null ? null : (int) $row->assigned_to,
                false,
                false,
                $row->deleted_at !== null,
            ),
            changes: is_array($changes) ? $changes : null,
            note: $row->note === null ? null : (string) $row->note,
            ipAddress: $row->ip_address === null ? null : (string) $row->ip_address,
            userAgent: $row->user_agent === null ? null : (string) $row->user_agent,
        );
    }
}
