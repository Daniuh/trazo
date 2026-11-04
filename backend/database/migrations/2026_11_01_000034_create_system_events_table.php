<?php

declare(strict_types=1);

// Eventos del sistema agrupados por huella: lo que se repite suma al contador en la misma fila, con la primera y la
// última vez. Estado abierto → revisado → resuelto; si un resuelto vuelve a ocurrir, se reabre.
// Documentado en docs/base-de-datos/diccionario/system-events.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_events', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('system_event_type_id');
            // SHA-256 del tipo y el origen: la misma falla cae siempre en la misma fila
            $table->char('fingerprint', 64)->unique();
            $table->string('message', 255);
            // Datos para entender el evento, sin secretos (sin tokens, contraseñas ni contenido de jobs)
            $table->json('context')->nullable();
            $table->unsignedInteger('occurrences')->default(1);
            $table->timestamp('first_seen_at')->useCurrent();
            $table->timestamp('last_seen_at')->useCurrent();
            // Identificador de la última petición que lo produjo, para cruzarlo con los logs
            $table->string('trace_id', 64)->nullable();
            $table->string('status', 15)->default('open');
            $table->unsignedBigInteger('status_changed_by')->nullable();
            $table->timestamp('status_changed_at')->nullable();

            $table->foreign('system_event_type_id')->references('id')->on('system_event_types')->restrictOnDelete();
            $table->foreign('status_changed_by')->references('id')->on('users')->restrictOnDelete();

            // La lista va de lo más reciente a lo más antiguo, casi siempre filtrada por estado
            $table->index(['status', 'last_seen_at']);
            $table->index(['system_event_type_id', 'last_seen_at']);
            $table->index('last_seen_at');
            $table->index('status_changed_by');
        });

        DB::statement("ALTER TABLE system_events ADD CONSTRAINT system_events_status_check CHECK (status IN ('open', 'acknowledged', 'resolved'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('system_events');
    }
};
