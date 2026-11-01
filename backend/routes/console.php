<?php

declare(strict_types=1);

// Tareas programadas (las ejecuta el servicio scheduler).
// Documentado en docs/backend/auth.md y docs/backend/system.md

use Illuminate\Support\Facades\Schedule;

// Borra los tokens de sesión vencidos hace más de un día
Schedule::command('sanctum:prune-expired --hours=24')->daily();

// Salud: señales de vida y eventos por piezas caídas o cola acumulada
Schedule::command('system:check')->everyMinute()->withoutOverlapping();

// Borra los eventos del sistema resueltos hace más de 90 días (configurable)
Schedule::command('system:prune-events')->dailyAt('03:30');
