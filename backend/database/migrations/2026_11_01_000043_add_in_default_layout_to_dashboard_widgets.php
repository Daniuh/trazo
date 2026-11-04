<?php

declare(strict_types=1);

// Qué widgets arman el diseño original del dashboard (ADR-0081). Los demás siguen en "Agregar widget".
// Solo agrega: los widgets que dejan el diseño original no se borran ni cambian. Documentado en docs/base-de-datos/diccionario/dashboard.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Los que el diseño centrado en el riesgo reemplaza
    private const OUT_OF_DEFAULT = [
        'total_incidents', 'pending_incidents', 'overdue_incidents', 'unassigned_incidents',
        'incidents_by_status', 'incidents_by_priority', 'created_vs_resolved',
    ];

    public function up(): void
    {
        Schema::table('dashboard_widgets', function (Blueprint $table): void {
            $table->boolean('in_default_layout')->default(true)->after('is_active');
        });

        DB::table('dashboard_widgets')->whereIn('code', self::OUT_OF_DEFAULT)->update(['in_default_layout' => false]);
    }

    public function down(): void
    {
        Schema::table('dashboard_widgets', function (Blueprint $table): void {
            $table->dropColumn('in_default_layout');
        });
    }
};
