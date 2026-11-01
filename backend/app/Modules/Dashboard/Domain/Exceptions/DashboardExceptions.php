<?php

declare(strict_types=1);

// Errores de negocio del dashboard, cada uno con su código estable para el catálogo de mensajes del front.
// Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Domain\Exceptions;

use App\Modules\Dashboard\Domain\WidgetDefinition;
use App\Modules\Shared\Domain\Exceptions\RuleViolationException;

final class DashboardExceptions
{
    // El widget no existe, está retirado o la persona no tiene el permiso que exige
    public static function notAvailable(string $code): RuleViolationException
    {
        return new RuleViolationException('WIDGET_NOT_AVAILABLE', ['widget' => $code]);
    }

    public static function duplicated(string $code): RuleViolationException
    {
        return new RuleViolationException('WIDGET_DUPLICATED', ['widget' => $code]);
    }

    public static function outOfGrid(string $code): RuleViolationException
    {
        return new RuleViolationException('WIDGET_OUT_OF_GRID', ['widget' => $code]);
    }

    public static function sizeOutOfRange(WidgetDefinition $definition): RuleViolationException
    {
        return new RuleViolationException('WIDGET_SIZE_OUT_OF_RANGE', [
            'widget' => $definition->code->value,
            'min_w' => $definition->minW,
            'max_w' => $definition->maxW,
            'min_h' => $definition->minH,
            'max_h' => $definition->maxH,
        ]);
    }

    public static function overlap(string $first, string $second): RuleViolationException
    {
        return new RuleViolationException('WIDGETS_OVERLAP', ['widget' => $first, 'other' => $second]);
    }
}
