<?php

declare(strict_types=1);

// Historial de cada incidente. Solo de escritura: nunca se edita ni se borra.
// Documentado en docs/base-de-datos/diccionario/incident-activities.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_activities', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('incident_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedTinyInteger('activity_type_id');
            // Valores anteriores y nuevos de lo que cambió
            $table->json('changes')->nullable();
            // 45 caracteres alcanzan para IPv6
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            // Sin updated_at: el registro no cambia nunca
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('incident_id')->references('id')->on('incidents')->restrictOnDelete();
            $table->foreign('user_id')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('activity_type_id')->references('id')->on('activity_types')->restrictOnDelete();

            // Historial del detalle, en orden
            $table->index(['incident_id', 'created_at']);
            // Actividad por usuario (auditoría global)
            $table->index(['user_id', 'created_at']);
            $table->index('activity_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_activities');
    }
};
