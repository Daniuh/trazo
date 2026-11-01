<?php

declare(strict_types=1);

// Errores de negocio de los incidentes. Cada uno lleva su código estable para el catálogo de mensajes del front.
// Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Domain\Exceptions;

use App\Modules\Incidents\Domain\Denial;
use App\Modules\Shared\Domain\Exceptions\ConflictException;
use App\Modules\Shared\Domain\Exceptions\DomainException;
use App\Modules\Shared\Domain\Exceptions\ForbiddenException;
use App\Modules\Shared\Domain\Exceptions\ResourceNotFoundException;
use App\Modules\Shared\Domain\Exceptions\RuleViolationException;

final class IncidentExceptions
{
    public static function denied(Denial $denial): DomainException
    {
        return match ($denial) {
            Denial::NotVisible => new ResourceNotFoundException($denial->value),
            Denial::Forbidden => new ForbiddenException,
            Denial::OwnReopenRequest, Denial::CommentNotOwn => new ForbiddenException($denial->value),
            default => new ConflictException($denial->value),
        };
    }

    // Alguien guardó otro cambio desde que esta persona abrió el incidente
    public static function versionConflict(): ConflictException
    {
        return new ConflictException('VERSION_CONFLICT');
    }

    public static function invalidTransition(string $from, string $to): ConflictException
    {
        return new ConflictException('INVALID_STATUS_TRANSITION', ['from' => $from, 'to' => $to]);
    }

    // El estado de destino pide explicar el cambio (cómo se resolvió, por qué se cancela)
    public static function noteRequired(string $status): RuleViolationException
    {
        return new RuleViolationException('STATUS_NOTE_REQUIRED', ['status' => $status]);
    }

    // La exportación pasa del tope: hay que acotar los filtros
    public static function exportTooLarge(int $total, int $max): RuleViolationException
    {
        return new RuleViolationException('EXPORT_TOO_LARGE', ['total' => $total, 'max' => $max]);
    }

    public static function invalidAssignee(): RuleViolationException
    {
        return new RuleViolationException('INVALID_ASSIGNEE');
    }
}
