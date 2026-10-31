<?php

declare(strict_types=1);

// Inserta solo las filas cuya clave natural todavía no existe.
// No se usa INSERT IGNORE: MySQL gasta un valor del autoincremental en cada intento aunque la fila ya exista,
// y los catálogos (TINYINT, máximo 255) se agotarían tras unas decenas de arranques. Documentado en docs/base-de-datos/seeders.md

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;

trait InsertsMissingRows
{
    /**
     * @param  list<array<string, scalar>>  $rows
     */
    private function insertMissing(string $table, string $key, array $rows): void
    {
        $existing = DB::table($table)->pluck($key)->flip();
        $missing = array_values(array_filter($rows, fn (array $row): bool => ! $existing->has($row[$key])));

        if ($missing === []) {
            return;
        }

        $now = now();
        DB::table($table)->insert(array_map(fn (array $row): array => [...$row, 'created_at' => $now, 'updated_at' => $now], $missing));
    }
}
