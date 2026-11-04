<?php

declare(strict_types=1);

// Índices para las métricas del dashboard. Todas filtran por deleted_at (borrado lógico) y, para quien no ve todo,
// por creador o asignado (la regla de visibilidad). Llevar esas columnas en el índice permite contar sin leer la tabla:
// - Conteo por estado y prioridad: (status_id, priority_id, deleted_at, created_by, assigned_to) reemplaza a
//   (status_id, priority_id), que es su prefijo.
// - Carga por agente, "asignados a mí" y "sin asignar": (assigned_to, status_id, due_date, deleted_at, created_by)
//   reemplaza a (assigned_to, status_id).
// - Tendencia de creados y resueltos por día: (created_at, deleted_at) reemplaza a (created_at) y se suma
//   (resolved_at, deleted_at).
// Documentado en docs/base-de-datos/rendimiento.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidents', function (Blueprint $table): void {
            $table->index(['status_id', 'priority_id', 'deleted_at', 'created_by', 'assigned_to'], 'incidents_dashboard_counts_index');
            $table->index(['assigned_to', 'status_id', 'due_date', 'deleted_at', 'created_by'], 'incidents_dashboard_assignee_index');
            $table->index(['created_at', 'deleted_at'], 'incidents_created_deleted_index');
            $table->index(['resolved_at', 'deleted_at'], 'incidents_resolved_deleted_index');
        });
        Schema::table('incidents', function (Blueprint $table): void {
            $table->dropIndex(['status_id', 'priority_id']);
            $table->dropIndex(['assigned_to', 'status_id']);
            $table->dropIndex(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table): void {
            $table->index(['status_id', 'priority_id']);
            $table->index(['assigned_to', 'status_id']);
            $table->index('created_at');
        });
        Schema::table('incidents', function (Blueprint $table): void {
            $table->dropIndex('incidents_dashboard_counts_index');
            $table->dropIndex('incidents_dashboard_assignee_index');
            $table->dropIndex('incidents_created_deleted_index');
            $table->dropIndex('incidents_resolved_deleted_index');
        });
    }
};
