<?php

declare(strict_types=1);

// Una base creada con los nombres de migración de antes (2026_10_DD) se pone al día sola y no vuelve a migrar nada.
// Documentado en docs/backend/shared.md

use App\Modules\Shared\Infrastructure\Database\MigrationNames;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

uses(RefreshDatabase::class);

it('renombra las filas viejas, conserva número y orden, y se puede repetir', function (): void {
    DB::table('migrations')->where('migration', '2026_11_01_000001_create_users_table')
        ->update(['migration' => '2026_10_21_000001_create_users_table']);
    DB::table('migrations')->where('migration', '2026_11_01_000043_add_in_default_layout_to_dashboard_widgets')
        ->update(['migration' => '2026_10_31_000043_add_in_default_layout_to_dashboard_widgets']);

    $names = app(MigrationNames::class);

    expect($names->update())->toBe(2)
        ->and($names->update())->toBe(0)
        ->and(DB::table('migrations')->where('migration', 'like', '2026_10_%')->count())->toBe(0)
        ->and(DB::table('migrations')->where('migration', '2026_11_01_000001_create_users_table')->exists())->toBeTrue();
});

it('antes de migrar pone al día los nombres, así no repite migraciones ya aplicadas', function (): void {
    DB::table('migrations')->where('migration', '2026_11_01_000002_create_roles_table')
        ->update(['migration' => '2026_10_21_000002_create_roles_table']);

    // Desde la consola, Laravel emite este evento antes de cada comando; Artisan::call no lo hace
    event(new CommandStarting('migrate', new ArrayInput([]), new NullOutput));

    expect(Artisan::call('migrate', ['--force' => true]))->toBe(0)
        ->and(DB::table('migrations')->where('migration', 'like', '%create_roles_table')->pluck('migration')->all())
        ->toBe(['2026_11_01_000002_create_roles_table']);
});
