<?php

declare(strict_types=1);

// Siembra completa. Todos los seeders son idempotentes: ejecutarlos varias veces no duplica nada.
// Documentado en docs/base-de-datos/seeders.md

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            IncidentCatalogSeeder::class,
            AccessControlSeeder::class,
            MfaPolicySeeder::class,
            DashboardWidgetSeeder::class,
            DemoUserSeeder::class,
            IncidentVolumeSeeder::class,
        ]);
    }
}
