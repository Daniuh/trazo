<?php

declare(strict_types=1);

// Registra eventos del sistema en una sola sentencia: si la huella ya existe, suma al contador, actualiza mensaje,
// contexto y última vez, y reabre lo resuelto. Nunca hace fallar lo que se estaba haciendo: si la base no responde,
// el evento queda solo en el log de Laravel. Documentado en docs/backend/system.md

namespace App\Modules\System\Infrastructure;

use App\Modules\Shared\Application\Ports\SystemEvents;
use App\Modules\Shared\Application\SystemEventType;
use App\Modules\Shared\Http\Middleware\AssignRequestId;
use App\Modules\System\Domain\Fingerprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

final class DatabaseSystemEvents implements SystemEvents
{
    // Al reabrir se borra quién lo había resuelto; el estado va al final porque MySQL aplica las asignaciones en orden
    private const UPSERT = <<<'SQL'
        INSERT INTO system_events
            (system_event_type_id, fingerprint, message, context, occurrences, first_seen_at, last_seen_at, trace_id, status)
        VALUES (?, ?, ?, ?, 1, ?, ?, ?, 'open') AS new
        ON DUPLICATE KEY UPDATE
            occurrences = system_events.occurrences + 1,
            message = new.message,
            context = new.context,
            last_seen_at = new.last_seen_at,
            trace_id = new.trace_id,
            status_changed_by = IF(system_events.status = 'resolved', NULL, system_events.status_changed_by),
            status_changed_at = IF(system_events.status = 'resolved', NULL, system_events.status_changed_at),
            status = IF(system_events.status = 'resolved', 'open', system_events.status)
        SQL;

    /** @var array<string, int>|null */
    private ?array $types = null;

    public function record(SystemEventType $type, string $source, string $message, array $context = []): void
    {
        try {
            $now = now('UTC')->format('Y-m-d H:i:s');
            DB::statement(self::UPSERT, [
                $this->typeId($type),
                Fingerprint::of($type->value, $source),
                Str::limit($message, 252),
                $context === [] ? null : json_encode($context, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                $now,
                $now,
                $this->traceId(),
            ]);
        } catch (Throwable $error) {
            Log::error('No se pudo registrar un evento del sistema', ['type' => $type->value, 'error' => $error->getMessage()]);
        }
    }

    private function typeId(SystemEventType $type): int
    {
        $this->types ??= DB::table('system_event_types')->pluck('id', 'code')->map(fn (mixed $id): int => (int) $id)->all();

        return $this->types[$type->value] ?? throw new LogicException("Falta el tipo de evento {$type->value} en el catálogo");
    }

    // Solo dentro de una petición: en la cola o en el scheduler no hay ruta ni a qué cruzarlo
    private function traceId(): ?string
    {
        $request = request();

        return $request->route() === null ? null : AssignRequestId::current($request);
    }
}
