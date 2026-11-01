<?php

declare(strict_types=1);

// Nombres y apellidos por separado (ADR-0086). "name" deja de escribirse: pasa a ser una columna calculada por MySQL
// con los dos, así todo lo que lee el nombre completo sigue igual y nunca puede quedar desalineado. Los nombres que ya
// existían (solo de demostración y de pruebas) se separan una vez con una regla simple.
// Documentado en docs/base-de-datos/diccionario/users.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Misma regla que PersonName::full(): nombres, un espacio y apellidos, sin espacios sobrantes
    private const FULL_NAME = "TRIM(CONCAT(given_names, ' ', family_names))";

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('given_names', 80)->nullable()->after('ulid');
            $table->string('family_names', 80)->nullable()->after('given_names');
        });

        DB::table('users')->orderBy('id')->select(['id', 'name'])->each(function (object $user): void {
            [$given, $family] = $this->split((string) $user->name);
            DB::table('users')->where('id', $user->id)->update(['given_names' => $given, 'family_names' => $family]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['is_active', 'name']);
            $table->dropColumn('name');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('given_names', 80)->nullable(false)->change();
            $table->string('family_names', 80)->nullable(false)->change();
            $table->string('name', 161)->storedAs(self::FULL_NAME)->after('family_names');
            // Listas de personas ordenadas por apellido (selector de asignados, administración, auditoría)
            $table->index(['is_active', 'family_names', 'given_names']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['is_active', 'family_names', 'given_names']);
            $table->dropColumn('name');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('name', 161)->nullable()->after('ulid');
        });
        DB::statement('UPDATE users SET name = '.self::FULL_NAME);

        Schema::table('users', function (Blueprint $table): void {
            $table->string('name', 161)->nullable(false)->change();
            $table->dropColumn(['given_names', 'family_names']);
            $table->index(['is_active', 'name']);
        });
    }

    /**
     * Dos palabras: nombre y apellido. Tres: un nombre y dos apellidos. Cuatro o más: dos nombres y el resto apellidos.
     *
     * @return array{string, string}
     */
    private function split(string $name): array
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $given = count($words) >= 4 ? 2 : 1;

        return [
            implode(' ', array_slice($words, 0, $given)),
            implode(' ', array_slice($words, $given)),
        ];
    }
};
