<?php

declare(strict_types=1);

// Una acción de sistema sin más datos que el código de reautenticación (reiniciar, limpiar caché, descartar un job).
// Documentado en docs/backend/system.md

namespace App\Modules\System\Http\Requests;

final class ActionRequest extends SystemRequest {}
