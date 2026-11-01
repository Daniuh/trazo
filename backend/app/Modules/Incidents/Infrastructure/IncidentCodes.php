<?php

declare(strict_types=1);

// Numeración de los códigos de incidente: un contador por año (incident_code_sequences) que se toma con bloqueo dentro
// de la misma transacción que crea el incidente, así dos creaciones a la vez nunca reciben el mismo número. También
// numera en bloque los que llegan sin código definitivo (datos de demostración y la migración del formato anterior).
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Domain\IncidentCode;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Support\Facades\DB;

final readonly class IncidentCodes
{
    public function __construct(private string $prefix, private string $timezone) {}

    public static function fromConfig(): self
    {
        return new self((string) config('trazo.incidents.code_prefix'), (string) config('trazo.dashboard.timezone'));
    }

    // El siguiente código del año en curso (en la zona horaria del negocio)
    public function next(DateTimeImmutable $now): string
    {
        $year = (int) $now->setTimezone(new DateTimeZone($this->timezone))->format('Y');

        // Una sola sentencia crea o sube el contador y deja el número en LAST_INSERT_ID de esta conexión;
        // la fila queda bloqueada hasta que termine la transacción
        DB::statement(
            'INSERT INTO incident_code_sequences (year, last_number) VALUES (?, LAST_INSERT_ID(1))
             ON DUPLICATE KEY UPDATE last_number = LAST_INSERT_ID(last_number + 1)',
            [$year],
        );
        $number = (int) DB::selectOne('SELECT LAST_INSERT_ID() AS number')->number;

        return IncidentCode::format($this->prefix, $year, $number);
    }

    // Numera, por año de creación y en orden, los incidentes cuyo código empieza como $pending; sigue desde donde
    // va el contador de cada año y lo deja al día
    public function assignPending(string $pending): void
    {
        // Desfase de la zona del negocio ("-05:00"); va como parámetro, nunca pegado al SQL
        $offset = (new DateTimeImmutable('now', new DateTimeZone($this->timezone)))->format('P');
        $like = $pending.'%';

        DB::transaction(function () use ($offset, $like): void {
            $counts = DB::table('incidents')->where('code', 'like', $like)
                ->selectRaw("YEAR(CONVERT_TZ(created_at, '+00:00', ?)) AS year, COUNT(*) AS total", [$offset])
                ->groupBy('year')
                ->get();
            if ($counts->isEmpty()) {
                return;
            }

            // El número arranca después del último del año, con cuatro cifras como mínimo (como IncidentCode::format)
            DB::statement(
                "UPDATE incidents i
                 JOIN (
                     SELECT id, year, ROW_NUMBER() OVER (PARTITION BY year ORDER BY created_at, id) AS position
                     FROM (SELECT id, created_at, YEAR(CONVERT_TZ(created_at, '+00:00', ?)) AS year FROM incidents WHERE code LIKE ?) pending
                 ) n ON n.id = i.id
                 LEFT JOIN incident_code_sequences s ON s.year = n.year
                 SET i.code = CONCAT(?, '-', LPAD(MOD(n.year, 100), 2, '0'), '-',
                     LPAD(COALESCE(s.last_number, 0) + n.position, GREATEST(4, CHAR_LENGTH(COALESCE(s.last_number, 0) + n.position)), '0'))",
                [$offset, $like, strtoupper($this->prefix)],
            );

            foreach ($counts as $count) {
                DB::statement(
                    'INSERT INTO incident_code_sequences (year, last_number) VALUES (?, ?) AS new
                     ON DUPLICATE KEY UPDATE last_number = incident_code_sequences.last_number + new.last_number',
                    [(int) $count->year, (int) $count->total],
                );
            }
        });
    }
}
