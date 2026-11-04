<?php

declare(strict_types=1);

// Diseño del dashboard de cada persona: posición, tamaño y visibilidad de cada widget. Sin filas = diseño original.
// Un widget oculto conserva su lugar, para volver a donde estaba si se agrega de nuevo.
// Documentado en docs/base-de-datos/diccionario/dashboard.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_dashboard_widgets', function (Blueprint $table): void {
            // El diseño es una preferencia: se va con la persona o con el widget
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('dashboard_widget_id');
            $table->foreign('dashboard_widget_id')->references('id')->on('dashboard_widgets')->cascadeOnDelete();

            $table->unsignedTinyInteger('x');
            $table->unsignedSmallInteger('y');
            $table->unsignedTinyInteger('w');
            $table->unsignedTinyInteger('h');
            $table->boolean('is_visible');
            $table->timestamps();

            $table->primary(['user_id', 'dashboard_widget_id']);
            $table->index('dashboard_widget_id');
        });

        DB::statement('ALTER TABLE user_dashboard_widgets ADD CONSTRAINT chk_user_dashboard_widgets_grid CHECK (w >= 1 AND h >= 1 AND x + w <= 12)');
    }

    public function down(): void
    {
        Schema::dropIfExists('user_dashboard_widgets');
    }
};
