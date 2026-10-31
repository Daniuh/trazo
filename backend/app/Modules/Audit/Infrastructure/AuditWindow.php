<?php

declare(strict_types=1);

// Lo que comparten las dos consultas de la auditoría: rango de fechas, cursor, orden (fecha, id) de mayor a menor y
// una fila de más para saber si queda otra tanda. Así nunca se cuenta la tabla completa.
// Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Infrastructure;

use App\Modules\Audit\Domain\AuditPeriod;
use App\Modules\Shared\Domain\TimeCursor;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use stdClass;

final class AuditWindow
{
    private const SQL_DATE = 'Y-m-d H:i:s';

    public static function apply(Builder $query, string $table, AuditPeriod $period, ?TimeCursor $cursor, int $perPage): void
    {
        $query->whereBetween("{$table}.created_at", [self::sql($period->from), self::sql($period->to)]);

        if ($cursor !== null) {
            $date = $cursor->sqlDate();
            $query->where(fn (Builder $after) => $after
                ->where("{$table}.created_at", '<', $date)
                ->orWhere(fn (Builder $same) => $same->where("{$table}.created_at", $date)->where("{$table}.id", '<', $cursor->id)));
        }

        $query->orderByDesc("{$table}.created_at")->orderByDesc("{$table}.id")->limit($perPage + 1);
    }

    /**
     * Separa la fila de más y arma el cursor desde la última entregada.
     *
     * @param  Collection<int, stdClass>  $rows
     * @return array{list<stdClass>, TimeCursor|null}
     */
    public static function split(Collection $rows, int $perPage): array
    {
        $items = array_values($rows->take($perPage)->all());
        $last = end($items);
        $next = $rows->count() > $perPage && $last !== false
            ? new TimeCursor(self::date((string) $last->created_at), (int) $last->id)
            : null;

        return [$items, $next];
    }

    public static function date(string $value): DateTimeImmutable
    {
        return new DateTimeImmutable($value, new DateTimeZone('UTC'));
    }

    // Las fechas se guardan en UTC
    private static function sql(DateTimeImmutable $date): string
    {
        return $date->setTimezone(new DateTimeZone('UTC'))->format(self::SQL_DATE);
    }
}
