<?php

declare(strict_types=1);

// Nota escrita por la persona en un cambio: la solución, el motivo de una cancelación o de una reapertura.
// Columna propia (no dentro de "changes") para que se lea y se consulte sin desarmar el JSON.
// Documentado en docs/base-de-datos/diccionario/incident-activities.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_activities', function (Blueprint $table): void {
            $table->string('note', 1000)->nullable()->after('changes');
        });
    }

    public function down(): void
    {
        Schema::table('incident_activities', function (Blueprint $table): void {
            $table->dropColumn('note');
        });
    }
};
