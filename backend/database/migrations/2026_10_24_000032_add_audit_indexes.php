<?php

declare(strict_types=1);

// Índices de la auditoría global: el listado va de lo más reciente a lo más antiguo, por fecha y, si se filtra, por
// tipo. InnoDB agrega el id al final de cada índice, así que (created_at) ya ordena por (created_at, id), que es el
// cursor. Solo se agregan índices. Documentado en docs/base-de-datos/diccionario/incident-activities.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incident_activities', function (Blueprint $table): void {
            $table->index('created_at');
            $table->index(['activity_type_id', 'created_at']);
        });

        Schema::table('admin_actions', function (Blueprint $table): void {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('incident_activities', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['activity_type_id', 'created_at']);
        });

        Schema::table('admin_actions', function (Blueprint $table): void {
            $table->dropIndex(['created_at']);
        });
    }
};
