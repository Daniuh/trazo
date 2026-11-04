<?php

declare(strict_types=1);

// Los conteos del listado (vencidos, rango de vencimiento) también filtran por deleted_at (borrado lógico).
// Sumar esa columna a los índices permite contar sin leer la tabla: con 100.000 incidentes, el conteo de vencidos
// pasa de 27 ms a 3 ms. Los índices nuevos empiezan igual que los anteriores, así que los reemplazan sin perder usos.
// Documentado en docs/base-de-datos/rendimiento.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table): void {
            $table->index(['status_id', 'due_date', 'deleted_at'], 'incidents_status_due_deleted_index');
            $table->index(['due_date', 'deleted_at'], 'incidents_due_deleted_index');
        });

        Schema::table('incidents', function (Blueprint $table): void {
            $table->dropIndex(['status_id', 'due_date']);
            $table->dropIndex(['due_date']);
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table): void {
            $table->index(['status_id', 'due_date']);
            $table->index('due_date');
        });

        Schema::table('incidents', function (Blueprint $table): void {
            $table->dropIndex('incidents_status_due_deleted_index');
            $table->dropIndex('incidents_due_deleted_index');
        });
    }
};
