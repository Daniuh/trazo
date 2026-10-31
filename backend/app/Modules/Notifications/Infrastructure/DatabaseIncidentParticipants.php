<?php

declare(strict_types=1);

// Lee de la base quiénes están relacionados con un incidente: creador, asignado, quienes escribieron en la
// conversación y quien pidió la reapertura que un registro del historial decidió. Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Infrastructure;

use App\Modules\Notifications\Application\Ports\IncidentParticipants;
use App\Modules\Notifications\Domain\Participants;
use App\Modules\Notifications\Domain\Recipients;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

final readonly class DatabaseIncidentParticipants implements IncidentParticipants
{
    // La decisión y su registro se escriben en la misma transacción; este margen cubre el cambio de segundo
    private const DECISION_MARGIN_SECONDS = 2;

    public function ofConversation(int $incidentId): ?Participants
    {
        $incident = DB::table('incidents')->where('id', $incidentId)->first(['created_by', 'assigned_to']);
        if ($incident === null) {
            return null;
        }
        $commenters = array_values(DB::table('incident_comments')->where('incident_id', $incidentId)->whereNull('deleted_at')
            ->distinct()->pluck('user_id')->map(fn (mixed $id): int => (int) $id)->all());

        return new Participants(
            (int) $incident->created_by,
            $incident->assigned_to === null ? null : (int) $incident->assigned_to,
            $commenters,
        );
    }

    public function ofActivity(int $activityId): ?Participants
    {
        $row = DB::table('incident_activities as a')
            ->join('activity_types as t', 't.id', '=', 'a.activity_type_id')
            ->join('incidents as i', 'i.id', '=', 'a.incident_id')
            ->join('priorities as p', 'p.id', '=', 'i.priority_id')
            ->where('a.id', $activityId)
            ->first(['a.incident_id', 'a.user_id', 'a.changes', 'a.created_at', 't.code', 'i.created_by', 'i.assigned_to', 'p.code as priority']);
        if ($row === null) {
            return null;
        }

        // Crear y asignar dicen a quién quedó asignado en ese momento; el de ahora pudo cambiar antes de que corra la cola
        $fromRecord = in_array((string) $row->code, ['created', 'assigned'], true);

        return new Participants(
            (int) $row->created_by,
            $this->assignee($row->changes, $fromRecord ? null : $row->assigned_to),
            reopenRequester: match ((string) $row->code) {
                'reopened' => $this->requester((int) $row->incident_id, (int) $row->user_id, 'approved', (string) $row->created_at),
                'reopen_rejected' => $this->requester((int) $row->incident_id, (int) $row->user_id, 'rejected', (string) $row->created_at),
                default => null,
            },
            critical: $row->priority === Recipients::CRITICAL_PRIORITY,
        );
    }

    // Si el registro cambió el asignado, cuenta el que dejó ese registro; si no, $current (vacío si no aplica)
    private function assignee(mixed $changes, mixed $current): ?int
    {
        $data = is_string($changes) ? json_decode($changes, true) : null;
        if (is_array($data) && is_array($data['assignee'] ?? null) && array_key_exists('to', $data['assignee'])) {
            $to = $data['assignee']['to'];

            return is_array($to) && is_string($to['id'] ?? null) ? $this->userId($to['id']) : null;
        }

        return $current === null ? null : (int) $current;
    }

    private function userId(string $ulid): ?int
    {
        $id = DB::table('users')->where('ulid', $ulid)->value('id');

        return $id === null ? null : (int) $id;
    }

    // Quien pidió la reapertura que esta misma persona decidió en este mismo momento (una reapertura directa no tiene)
    private function requester(int $incidentId, int $decidedBy, string $status, string $at): ?int
    {
        $moment = new DateTimeImmutable($at);
        $requester = DB::table('incident_reopen_requests')
            ->where('incident_id', $incidentId)
            ->where('decided_by', $decidedBy)
            ->where('status', $status)
            ->whereBetween('decided_at', [
                $moment->modify('-'.self::DECISION_MARGIN_SECONDS.' seconds')->format('Y-m-d H:i:s'),
                $moment->modify('+'.self::DECISION_MARGIN_SECONDS.' seconds')->format('Y-m-d H:i:s'),
            ])
            ->orderByDesc('id')
            ->value('requested_by');

        return $requester === null ? null : (int) $requester;
    }
}
