<?php

declare(strict_types=1);

// Incidentes: núcleo del sistema. Índices pensados para los filtros, el orden y el dashboard reales.
// Documentado en docs/base-de-datos/diccionario/incidents.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table): void {
            $table->id();
            $table->char('ulid', 26)->unique();
            // Código legible para las personas: INC-000123
            $table->string('code', 20)->unique();
            $table->string('title', 150);
            $table->text('description');

            $table->unsignedTinyInteger('priority_id');
            $table->unsignedTinyInteger('status_id');
            // Quién lo reportó: obligatorio e inmutable, base de la trazabilidad
            $table->unsignedBigInteger('created_by');
            // Quién lo atiende: opcional mientras está en la bandeja de "sin asignar"
            $table->unsignedBigInteger('assigned_to')->nullable();

            $table->dateTime('due_date');
            $table->timestamp('resolved_at')->nullable();
            // Bloqueo optimista: cada edición debe traer la versión que conocía
            $table->unsignedInteger('lock_version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            // RESTRICT en todo: nada con valor histórico se borra en cascada
            $table->foreign('priority_id')->references('id')->on('priorities')->restrictOnDelete();
            $table->foreign('status_id')->references('id')->on('statuses')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users')->restrictOnDelete();

            // Filtros combinados y conteos del dashboard
            $table->index(['status_id', 'priority_id']);
            // Conteo de vencidos
            $table->index(['status_id', 'due_date']);
            // Filtro por prioridad (y su FK)
            $table->index('priority_id');
            // Filtro por asignado y "mis incidentes"
            $table->index(['assigned_to', 'status_id']);
            // "Creados por mí" (y su FK)
            $table->index('created_by');
            // Rango y orden por vencimiento
            $table->index('due_date');
            // Rango y orden por creación (orden por defecto)
            $table->index('created_at');
            // Búsqueda por texto
            $table->fullText(['title', 'description']);
        });

        DB::statement('ALTER TABLE incidents ADD CONSTRAINT chk_incidents_lock_version CHECK (lock_version >= 1)');
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
