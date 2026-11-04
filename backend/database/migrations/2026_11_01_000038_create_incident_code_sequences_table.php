<?php

declare(strict_types=1);

// Contador de códigos de incidente por año: el último número entregado en cada año (TZ-26-0142 es el 142 de 2026).
// Se toma con bloqueo dentro de la transacción que crea el incidente. Documentado en
// docs/base-de-datos/diccionario/incident-code-sequences.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_code_sequences', function (Blueprint $table): void {
            // El año en la zona horaria del negocio, completo (2026)
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_code_sequences');
    }
};
