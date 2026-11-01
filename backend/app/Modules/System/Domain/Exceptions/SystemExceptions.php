<?php

declare(strict_types=1);

// Errores de las acciones de sistema. Cada uno lleva su código estable para el catálogo de mensajes del front.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Domain\Exceptions;

use App\Modules\Shared\Domain\Exceptions\ConflictException;
use App\Modules\Shared\Domain\Exceptions\ResourceNotFoundException;

final class SystemExceptions
{
    public static function maintenanceAlreadyEnabled(): ConflictException
    {
        return new ConflictException('MAINTENANCE_ALREADY_ENABLED');
    }

    public static function maintenanceAlreadyDisabled(): ConflictException
    {
        return new ConflictException('MAINTENANCE_ALREADY_DISABLED');
    }

    public static function noFailedJobs(): ConflictException
    {
        return new ConflictException('NO_FAILED_JOBS');
    }

    // Otra persona ya lo reintentó o lo descartó
    public static function failedJobNotFound(): ResourceNotFoundException
    {
        return new ResourceNotFoundException('FAILED_JOB_NOT_FOUND');
    }

    public static function eventNotFound(): ResourceNotFoundException
    {
        return new ResourceNotFoundException('SYSTEM_EVENT_NOT_FOUND');
    }
}
