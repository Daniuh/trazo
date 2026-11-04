<?php

declare(strict_types=1);

// Catálogo de tipos de notificación, cada uno con la pestaña del centro de notificaciones en la que aparece.
// Documentado en docs/base-de-datos/diccionario/notifications.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_types', function (Blueprint $table): void {
            $table->tinyIncrements('id');
            $table->string('code', 40)->unique();
            $table->string('name', 80);
            // conversations o activity: la pestaña del centro de notificaciones
            $table->string('category', 20);
            $table->timestamps();
        });

        DB::statement("ALTER TABLE notification_types ADD CONSTRAINT notification_types_category_check CHECK (category IN ('conversations', 'activity'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_types');
    }
};
