<?php

declare(strict_types=1);

// Notificaciones en MySQL. La lectura junta cada aviso con el mensaje o el registro del historial que lo originó, y
// deja sin datos los incidentes que la persona ya no puede ver (misma regla del listado, VisibilitySql).
// Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Infrastructure;

use App\Modules\Incidents\Domain\VisibilityScope;
use App\Modules\Incidents\Infrastructure\Models\Incident;
use App\Modules\Incidents\Infrastructure\VisibilitySql;
use App\Modules\Notifications\Application\Data\NotificationRecord;
use App\Modules\Notifications\Application\Data\NotifiedIncident;
use App\Modules\Notifications\Application\Ports\NotificationStore;
use App\Modules\Notifications\Domain\NotificationCategory;
use App\Modules\Notifications\Domain\NotificationType;
use DateTimeImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;
use stdClass;

final class DatabaseNotificationStore implements NotificationStore
{
    // Tope del contador de mensajes agrupados (SMALLINT UNSIGNED)
    private const MAX_OCCURRENCES = 65535;

    /** @var array<string, int>|null */
    private ?array $types = null;

    public function addComment(array $users, int $incidentId, int $commentId): void
    {
        $type = $this->typeId(NotificationType::CommentReceived);

        foreach ($users as $user) {
            DB::transaction(function () use ($user, $incidentId, $commentId, $type): void {
                // El bloqueo evita que dos mensajes simultáneos creen dos avisos sin leer del mismo incidente
                $pending = DB::table('notifications')->where('user_id', $user)
                    ->where('pending_comment_incident_id', $incidentId)->lockForUpdate()->first(['id', 'occurrences']);
                if ($pending !== null) {
                    DB::table('notifications')->where('id', $pending->id)->delete();
                }
                // Se vuelve a crear para que suba al primer lugar de la lista
                DB::table('notifications')->insert($this->row($user, $type, $incidentId) + [
                    'comment_id' => $commentId,
                    'occurrences' => min(self::MAX_OCCURRENCES, ($pending === null ? 0 : (int) $pending->occurrences) + 1),
                ]);
            });
        }
    }

    public function addActivity(array $users, int $incidentId, int $activityId): void
    {
        $rows = [];
        foreach ($users as $user => $type) {
            $rows[] = $this->row($user, $this->typeId($type), $incidentId) + ['activity_id' => $activityId];
        }
        DB::table('notifications')->insert($rows);
    }

    public function addNotice(array $users, int $incidentId, NotificationType $type): void
    {
        $typeId = $this->typeId($type);
        DB::table('notifications')->insert(array_map(fn (int $user): array => $this->row($user, $typeId, $incidentId), $users));
    }

    public function page(int $userId, NotificationCategory $category, VisibilityScope $scope, ?string $before, int $limit): array
    {
        $query = DB::table('notifications as n')
            ->join('notification_types as t', 't.id', '=', 'n.notification_type_id')
            ->join('incidents as i', 'i.id', '=', 'n.incident_id')
            ->leftJoin('incident_comments as c', 'c.id', '=', 'n.comment_id')
            ->leftJoin('users as cu', 'cu.id', '=', 'c.user_id')
            ->leftJoin('incident_activities as a', 'a.id', '=', 'n.activity_id')
            ->leftJoin('users as au', 'au.id', '=', 'a.user_id')
            ->where('n.user_id', $userId)
            // Por los ids de los tipos y no por la categoría unida: así MySQL recorre (user_id, id) hacia atrás y para
            // en la página, en vez de ordenar todas las de la persona (medido: 37 ms → 0,8 ms con 6.700 avisos)
            ->whereIn('n.notification_type_id', $this->typeIdsOf($category))
            ->orderByDesc('n.id')
            ->limit($limit)
            ->select([
                'n.ulid', 't.code', 'n.occurrences', 'n.read_at', 'n.created_at', 'n.incident_id',
                'i.ulid as incident_ulid', 'i.code as incident_code', 'i.title as incident_title', 'i.assigned_to',
                'c.body', 'c.deleted_at as comment_deleted_at', 'cu.name as comment_author',
                'a.changes', 'a.note', 'au.name as activity_author',
            ]);
        if ($before !== null) {
            // Por id y no por fecha: dos avisos del mismo segundo no se saltan ni se repiten
            $query->where('n.id', '<', fn (Builder $q) => $q->select('id')->from('notifications')
                ->where('ulid', $before)->where('user_id', $userId));
        }
        $rows = $query->get();
        $visible = $this->visible($userId, $scope, array_values($rows->pluck('incident_id')->map(fn (mixed $id): int => (int) $id)->all()));

        return array_values($rows->map(fn (stdClass $row): NotificationRecord => $this->record($row, isset($visible[(int) $row->incident_id])))->all());
    }

    public function unread(int $userId): array
    {
        $counts = DB::table('notifications as n')
            ->join('notification_types as t', 't.id', '=', 'n.notification_type_id')
            ->where('n.user_id', $userId)
            ->whereNull('n.read_at')
            ->groupBy('t.category')
            ->selectRaw('t.category, COUNT(*) as total')
            ->pluck('total', 'category');

        $unread = [];
        foreach (NotificationCategory::cases() as $category) {
            $unread[$category->value] = (int) ($counts[$category->value] ?? 0);
        }

        return $unread;
    }

    public function markRead(int $userId, string $ulid): bool
    {
        $notification = DB::table('notifications')->where('user_id', $userId)->where('ulid', $ulid);
        if (! $notification->exists()) {
            return false;
        }
        (clone $notification)->whereNull('read_at')->update(['read_at' => now()]);

        return true;
    }

    public function markAllRead(int $userId, NotificationCategory $category, ?string $incidentUlid): void
    {
        $query = DB::table('notifications')
            ->where('user_id', $userId)
            ->whereNull('read_at')
            ->whereIn('notification_type_id', $this->typeIdsOf($category));
        if ($incidentUlid !== null) {
            $query->whereIn('incident_id', fn (Builder $q) => $q->select('id')->from('incidents')->where('ulid', $incidentUlid));
        }
        $query->update(['read_at' => now()]);
    }

    public function prune(DateTimeImmutable $before): int
    {
        $deleted = 0;
        // De a pedazos, para no bloquear la tabla con un solo DELETE enorme
        do {
            $batch = DB::table('notifications')->where('created_at', '<', $before->format('Y-m-d H:i:s'))->limit(1000)->delete();
            $deleted += $batch;
        } while ($batch > 0);

        return $deleted;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(int $user, int $type, int $incidentId): array
    {
        return [
            'ulid' => Str::lower((string) Str::ulid()),
            'user_id' => $user,
            'notification_type_id' => $type,
            'incident_id' => $incidentId,
            'created_at' => now(),
        ];
    }

    private function typeId(NotificationType $type): int
    {
        $this->types ??= DB::table('notification_types')->pluck('id', 'code')->map(fn (mixed $id): int => (int) $id)->all();

        return $this->types[$type->value] ?? throw new LogicException("Tipo de notificación desconocido: {$type->value}");
    }

    /**
     * @return list<int>
     */
    private function typeIdsOf(NotificationCategory $category): array
    {
        return array_values(array_map(
            $this->typeId(...),
            array_filter(NotificationType::cases(), fn (NotificationType $type): bool => $type->category() === $category),
        ));
    }

    /**
     * Los incidentes de la página que la persona puede ver hoy (sin los eliminados).
     *
     * @param  list<int>  $ids
     * @return array<int, true>
     */
    private function visible(int $userId, VisibilityScope $scope, array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $query = Incident::query()->whereIn('incidents.id', array_unique($ids));
        VisibilitySql::apply($query, $userId, $scope);

        return array_fill_keys($query->pluck('incidents.id')->map(fn (mixed $id): int => (int) $id)->all(), true);
    }

    private function record(stdClass $row, bool $visible): NotificationRecord
    {
        $type = NotificationType::from((string) $row->code);
        $changes = is_string($row->changes) ? json_decode($row->changes, true) : null;
        $status = is_array($changes) ? ($changes['status']['to']['name'] ?? null) : null;
        $deleted = $row->comment_deleted_at !== null;

        return new NotificationRecord(
            (string) $row->ulid,
            $type,
            (int) $row->occurrences,
            new DateTimeImmutable((string) $row->created_at.' UTC'),
            $row->read_at === null ? null : new DateTimeImmutable((string) $row->read_at.' UTC'),
            ($row->comment_author ?? $row->activity_author) === null ? null : (string) ($row->comment_author ?? $row->activity_author),
            $visible ? new NotifiedIncident((string) $row->incident_ulid, (string) $row->incident_code, (string) $row->incident_title) : null,
            // Lo que se dijo solo se muestra si el incidente sigue a la vista
            $visible ? $this->excerpt($row->body ?? $row->note) : null,
            $visible && is_string($status) ? $status : null,
            $deleted,
            $this->unassigned($type, $changes, $row->assigned_to),
        );
    }

    // Sin responsable: al crearlo, según su registro (el de ahora pudo cambiar); al vencer, según el incidente
    private function unassigned(NotificationType $type, mixed $changes, mixed $assignedTo): bool
    {
        return match ($type) {
            NotificationType::UnassignedCreated => true,
            NotificationType::CriticalCreated => ! (is_array($changes) && isset($changes['assignee'])),
            NotificationType::IncidentOverdue => $assignedTo === null,
            default => false,
        };
    }

    private function excerpt(mixed $text): ?string
    {
        return is_string($text) ? Str::limit(Str::squish($text), 140) : null;
    }
}
