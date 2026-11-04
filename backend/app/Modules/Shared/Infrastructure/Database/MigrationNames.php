<?php

declare(strict_types=1);

// Pone al día los nombres de migración que guarda una base creada antes del cambio de nombres (ADR-0091): los
// archivos pasaron de 2026_10_DD_0000NN a 2026_11_01_0000NN, con el mismo número y el mismo orden. Corre antes de
// cada `migrate`, solo cambia filas de la tabla de control y se puede repetir. Documentado en docs/backend/shared.md

namespace App\Modules\Shared\Infrastructure\Database;

use Illuminate\Database\Connection;

final class MigrationNames
{
    /** Nombres de antes: fecha de octubre y número de seis cifras */
    private const LEGACY = '^2026_10_[0-9]{2}_0000[0-9]{2}_';

    private const CURRENT_DATE = '2026_11_01_';

    public function __construct(private readonly Connection $db) {}

    /** Renombra las filas viejas y devuelve cuántas cambió (0 si la base es nueva o ya estaba al día) */
    public function update(): int
    {
        $table = (string) config('database.migrations.table', 'migrations');
        if (! $this->db->getSchemaBuilder()->hasTable($table)) {
            return 0;
        }

        // El prefijo de fecha mide 11 caracteres: se cambia y el resto del nombre queda igual
        return $this->db->table($table)
            ->whereRaw('migration REGEXP ?', [self::LEGACY])
            ->update(['migration' => $this->db->raw("CONCAT('".self::CURRENT_DATE."', SUBSTRING(migration, 12))")]);
    }
}
