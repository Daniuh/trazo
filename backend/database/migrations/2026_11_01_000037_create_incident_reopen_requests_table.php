<?php

declare(strict_types=1);

// Solicitudes para reabrir un incidente cerrado o cancelado: quién la pide y por qué, y quién la decide.
// Solo puede haber una pendiente por incidente; lo garantiza la columna calculada pending_incident_id.
// Documentado en docs/base-de-datos/diccionario/incident-reopen-requests.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_reopen_requests', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('incident_id');
            $table->unsignedBigInteger('requested_by');
            $table->string('reason', 1000);
            $table->string('status', 10)->default('pending');
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->string('decision_note', 1000)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            // Igual al incidente mientras está pendiente y NULL después: el índice único admite varios NULL
            $table->unsignedBigInteger('pending_incident_id')->storedAs("IF(status = 'pending', incident_id, NULL)");

            // Las solicitudes son parte del historial: ni el incidente ni las personas se borran con ellas
            $table->foreign('incident_id')->references('id')->on('incidents')->restrictOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('decided_by')->references('id')->on('users')->restrictOnDelete();

            $table->unique('pending_incident_id');
            $table->index(['incident_id', 'created_at']);
        });

        DB::statement("ALTER TABLE incident_reopen_requests ADD CONSTRAINT chk_reopen_requests_status CHECK (status IN ('pending', 'approved', 'rejected'))");
        // Una decisión siempre dice quién y cuándo; una pendiente no tiene ninguna de las dos
        DB::statement("ALTER TABLE incident_reopen_requests ADD CONSTRAINT chk_reopen_requests_decision CHECK ((status = 'pending') = (decided_by IS NULL AND decided_at IS NULL))");
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_reopen_requests');
    }
};
