<?php

declare(strict_types=1);

// Tareas programadas (las ejecuta el servicio scheduler).
// Documentado en docs/backend/auth.md

use Illuminate\Support\Facades\Schedule;

// Borra los tokens de sesión vencidos hace más de un día
Schedule::command('sanctum:prune-expired --hours=24')->daily();
