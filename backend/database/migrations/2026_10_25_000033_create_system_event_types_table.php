<?php

declare(strict_types=1);

// Catálogo de eventos del sistema (error no controlado, job fallido, ingreso bloqueado…), con su categoría y su
// severidad. Documentado en docs/base-de-datos/diccionario/system-events.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_event_types', function (Blueprint $table): void {
            $table->tinyIncrements('id');
            $table->string('code', 40)->unique();
            $table->string('name', 80);
            // failure, infrastructure o security
            $table->string('category', 20);
            // info, warning o critical
            $table->string('severity', 10);
            $table->timestamps();
        });

        DB::statement("ALTER TABLE system_event_types ADD CONSTRAINT system_event_types_category_check CHECK (category IN ('failure', 'infrastructure', 'security'))");
        DB::statement("ALTER TABLE system_event_types ADD CONSTRAINT system_event_types_severity_check CHECK (severity IN ('info', 'warning', 'critical'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('system_event_types');
    }
};
