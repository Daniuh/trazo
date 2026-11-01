<?php

declare(strict_types=1);

// Catálogo de widgets del dashboard: tipo, topes de tamaño en la grilla de 12 columnas, tamaño y orden originales,
// y el permiso que exige cada uno (ninguno = lo ve todo el que entra al dashboard).
// Documentado en docs/base-de-datos/diccionario/dashboard.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_widgets', function (Blueprint $table): void {
            $table->smallIncrements('id');
            $table->string('code', 40)->unique();
            $table->string('name', 60);
            $table->string('description', 200);
            $table->string('kind', 10);
            // No se puede borrar un permiso que todavía protege un widget
            $table->unsignedSmallInteger('permission_id')->nullable();
            $table->foreign('permission_id')->references('id')->on('permissions')->restrictOnDelete();

            $table->unsignedSmallInteger('sort_order');
            $table->unsignedTinyInteger('min_w');
            $table->unsignedTinyInteger('max_w');
            $table->unsignedTinyInteger('min_h');
            $table->unsignedTinyInteger('max_h');
            $table->unsignedTinyInteger('default_w');
            $table->unsignedTinyInteger('default_h');
            // Un widget retirado deja de ofrecerse sin borrar el diseño de nadie
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('permission_id');
            $table->index(['is_active', 'sort_order']);
        });

        DB::statement("ALTER TABLE dashboard_widgets ADD CONSTRAINT chk_dashboard_widgets_kind CHECK (kind IN ('kpi', 'chart', 'list'))");
        DB::statement('ALTER TABLE dashboard_widgets ADD CONSTRAINT chk_dashboard_widgets_width CHECK (min_w >= 1 AND min_w <= default_w AND default_w <= max_w AND max_w <= 12)');
        DB::statement('ALTER TABLE dashboard_widgets ADD CONSTRAINT chk_dashboard_widgets_height CHECK (min_h >= 1 AND min_h <= default_h AND default_h <= max_h)');
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_widgets');
    }
};
