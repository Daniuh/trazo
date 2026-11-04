<?php

declare(strict_types=1);

// Flujo permitido entre estados, guardado como datos para poder cambiarlo sin tocar código.
// Documentado en docs/base-de-datos/diccionario/catalogos.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('status_transitions', function (Blueprint $table): void {
            $table->unsignedTinyInteger('from_status_id');
            $table->unsignedTinyInteger('to_status_id');

            $table->foreign('from_status_id')->references('id')->on('statuses')->restrictOnDelete();
            $table->foreign('to_status_id')->references('id')->on('statuses')->restrictOnDelete();

            $table->primary(['from_status_id', 'to_status_id']);
            $table->index('to_status_id');
        });

        DB::statement('ALTER TABLE status_transitions ADD CONSTRAINT chk_status_transitions_distinct CHECK (from_status_id <> to_status_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('status_transitions');
    }
};
