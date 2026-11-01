<?php

declare(strict_types=1);

// Rutas de la API (/api/v1). Cada módulo declara las suyas en Modules/<Módulo>/Http/routes.php.
// Documentado en docs/backend/index.md

$moduleRoutes = glob(app_path('Modules/*/Http/routes.php')) ?: [];
sort($moduleRoutes);

foreach ($moduleRoutes as $routes) {
    require $routes;
}
