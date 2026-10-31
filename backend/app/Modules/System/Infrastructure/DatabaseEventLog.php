<?php

declare(strict_types=1);

// Lectura de system_events: de lo más reciente a lo más antiguo por última vez, con filtros y cursor; cambio de
// estado y limpieza de los resueltos viejos. Documentado en docs/backend/system.md

namespace App\Modules\System\Infrastructure;

use App\Modules\Shared\Domain\TimeCursor;
use App\Modules\System\Application\Data\EventCriteria;
use App\Modules\System\Application\Data\EventPage;
use App\Modules\System\Application\Data\EventRecord;
use App\Modules\System\Application\Data\EventType;
use App\Modules\System\Application\Ports\SystemEventLog;
use App\Modules\System\Domain\EventStatus;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

final class DatabaseEventLog implements SystemEventLog
{
    private const SQL_DATE = 'Y-m-d H:i:s';

    public function page(EventCriteria $criteria): EventPage
    {
        $query = $this->base();
        if ($criteria->statuses !== []) {
            $query->whereIn('e.status', $criteria->statuses);
        }
        if ($criteria->severities !== []) {
            $query->whereIn('t.severity', $criteria->severities);
        }
        if ($criteria->types !== []) {
            $query->whereIn('t.code', $criteria->types);
        }
        if ($criteria->cursor !== null) {
            $date = $criteria->cursor->sqlDate();
            $id = $criteria->cursor->id;
            $query->where(fn (Builder $after) => $after
                ->where('e.last_seen_at', '<', $date)
                ->orWhere(fn (Builder $same) => $same->where('e.last_seen_at', $date)->where('e.id', '<', $id)));
        }

        $rows = $query->orderByDesc('e.last_seen_at')->orderByDesc('e.id')->limit($criteria->perPage + 1)->get();
        $items = array_values(array_map($this->toRecord(...), $rows->take($criteria->perPage)->all()));
        $last = end($items);
        $next = $rows->count() > $criteria->perPage && $last !== false ? new TimeCursor($last->lastSeenAt, $last->id) : null;

        return new EventPage($items, $next);
    }

    public function find(int $id): ?EventRecord
    {
        $row = $this->base()->where('e.id', $id)->first();

        return $row instanceof stdClass ? $this->toRecord($row) : null;
    }

    public function changeStatus(int $id, EventStatus $status, int $byUserId): void
    {
        DB::table('system_events')->where('id', $id)->update([
            'status' => $status->value,
            'status_changed_by' => $byUserId,
            'status_changed_at' => now('UTC')->format(self::SQL_DATE),
        ]);
    }

    public function types(): array
    {
        return array_values(DB::table('system_event_types')->orderBy('name')->get()->map(
            fn (stdClass $type): EventType => new EventType((string) $type->code, (string) $type->name, (string) $type->category, (string) $type->severity),
        )->all());
    }

    public function pruneResolvedBefore(DateTimeImmutable $limit): int
    {
        return DB::table('system_events')
            ->where('status', EventStatus::Resolved->value)
            ->where('status_changed_at', '<', $limit->setTimezone(new DateTimeZone('UTC'))->format(self::SQL_DATE))
            ->delete();
    }

    private function base(): Builder
    {
        return DB::table('system_events as e')
            ->join('system_event_types as t', 't.id', '=', 'e.system_event_type_id')
            ->leftJoin('users as u', 'u.id', '=', 'e.status_changed_by')
            ->select([
                'e.id', 'e.message', 'e.context', 'e.occurrences', 'e.first_seen_at', 'e.last_seen_at', 'e.trace_id',
                'e.status', 'e.status_changed_at', 't.code', 't.name', 't.category', 't.severity', 'u.name as changed_by',
            ]);
    }

    private function toRecord(stdClass $row): EventRecord
    {
        $context = $row->context === null ? [] : json_decode((string) $row->context, true, 8, JSON_THROW_ON_ERROR);

        return new EventRecord(
            (int) $row->id,
            new EventType((string) $row->code, (string) $row->name, (string) $row->category, (string) $row->severity),
            (string) $row->message,
            is_array($context) ? $context : [],
            (int) $row->occurrences,
            $this->date((string) $row->first_seen_at),
            $this->date((string) $row->last_seen_at),
            $row->trace_id === null ? null : (string) $row->trace_id,
            EventStatus::from((string) $row->status),
            $row->changed_by === null ? null : (string) $row->changed_by,
            $row->status_changed_at === null ? null : $this->date((string) $row->status_changed_at),
        );
    }

    private function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }
}
