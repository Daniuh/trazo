<?php

declare(strict_types=1);

// Estados que exigen una nota al llegar a ellos: cómo se resolvió o por qué se cancela.
// Las bases que ya existen marcan Resuelto y Cancelado aquí; en una nueva lo hace el seeder.
// Documentado en docs/base-de-datos/diccionario/catalogos.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('statuses', function (Blueprint $table): void {
            $table->boolean('requires_note')->default(false)->after('is_final');
        });

        DB::table('statuses')->whereIn('code', ['resolved', 'cancelled'])->update(['requires_note' => true]);
    }

    public function down(): void
    {
        Schema::table('statuses', function (Blueprint $table): void {
            $table->dropColumn('requires_note');
        });
    }
};
