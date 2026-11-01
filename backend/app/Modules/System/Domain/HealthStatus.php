<?php

declare(strict_types=1);

// Estado de un componente en el panel de salud: bien, atención o falla. Documentado en docs/backend/system.md

namespace App\Modules\System\Domain;

enum HealthStatus: string
{
    case Ok = 'ok';
    case Warning = 'warning';
    case Failing = 'failing';

    // El estado general es el peor de sus componentes
    public static function worst(self ...$statuses): self
    {
        return match (true) {
            in_array(self::Failing, $statuses, true) => self::Failing,
            in_array(self::Warning, $statuses, true) => self::Warning,
            default => self::Ok,
        };
    }
}
