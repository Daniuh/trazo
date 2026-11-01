<?php

declare(strict_types=1);

// Nivel de cada rol en la jerarquía: solo se administra a quien tiene un nivel menor. Es un dato, así un rol nuevo
// solo necesita su número. Los roles que ya existen reciben su nivel aquí. Documentado en docs/base-de-datos/diccionario/roles-y-permisos.md

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEVELS = ['requester' => 20, 'agent' => 40, 'supervisor' => 60, 'super_admin' => 100];

    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->unsignedTinyInteger('level')->default(0)->after('name');
        });

        foreach (self::LEVELS as $code => $level) {
            DB::table('roles')->where('code', $code)->update(['level' => $level]);
        }

        DB::statement('ALTER TABLE roles ADD CONSTRAINT roles_level_range CHECK (level BETWEEN 1 AND 100)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE roles DROP CHECK roles_level_range');

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn('level');
        });
    }
};
