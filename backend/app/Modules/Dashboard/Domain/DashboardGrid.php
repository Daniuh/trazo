<?php

declare(strict_types=1);

// Reglas de la grilla del dashboard: 12 columnas, topes de tamaño por widget y sin choques entre los visibles.
// También arma el diseño original a partir del orden del catálogo. Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Domain;

use App\Modules\Dashboard\Domain\Exceptions\DashboardExceptions;

final class DashboardGrid
{
    public const COLUMNS = 12;

    // Ningún diseño razonable llega tan abajo; el tope evita filas absurdas
    public const MAX_ROW = 200;

    /**
     * Diseño original: los widgets en el orden del catálogo, de izquierda a derecha y fila por fila.
     * Si a una fila le sobra espacio, el último widget crece (dentro de su tope) para no dejar huecos.
     *
     * @param  list<WidgetDefinition>  $definitions
     * @return array<string, Placement>
     */
    public static function defaults(array $definitions): array
    {
        $placements = [];
        $row = [];
        $x = 0;
        $y = 0;

        foreach ($definitions as $definition) {
            if ($x + $definition->defaultW > self::COLUMNS) {
                $y = self::closeRow($row, $x, $y, $placements);
                $row = [];
                $x = 0;
            }
            $row[] = [$definition, $x];
            $x += $definition->defaultW;
        }
        self::closeRow($row, $x, $y, $placements);

        return $placements;
    }

    /**
     * @param  list<WidgetLayout>  $layouts
     * @param  array<string, WidgetDefinition>  $allowed  Widgets que la persona puede usar, por código
     */
    public static function validate(array $layouts, array $allowed): void
    {
        $seen = [];
        $visible = [];

        foreach ($layouts as $layout) {
            $code = $layout->code->value;
            $definition = $allowed[$code] ?? throw DashboardExceptions::notAvailable($code);
            if (isset($seen[$code])) {
                throw DashboardExceptions::duplicated($code);
            }
            $seen[$code] = true;

            $place = $layout->placement;
            if ($place->x < 0 || $place->y < 0 || $place->x + $place->w > self::COLUMNS || $place->y > self::MAX_ROW) {
                throw DashboardExceptions::outOfGrid($code);
            }
            if (! $definition->fits($place->w, $place->h)) {
                throw DashboardExceptions::sizeOutOfRange($definition);
            }

            if ($layout->visible) {
                foreach ($visible as $other) {
                    if ($other->placement->overlaps($place)) {
                        throw DashboardExceptions::overlap($other->code->value, $code);
                    }
                }
                $visible[] = $layout;
            }
        }
    }

    // Lleva un lugar a los topes actuales del widget (por si el catálogo cambió después de guardarlo)
    public static function fit(WidgetDefinition $definition, Placement $placement): Placement
    {
        $w = max($definition->minW, min($definition->maxW, $placement->w));
        $h = max($definition->minH, min($definition->maxH, $placement->h));

        return new Placement(min($placement->x, self::COLUMNS - $w), $placement->y, $w, $h);
    }

    /**
     * Ubica la fila que se cierra y devuelve dónde empieza la siguiente.
     *
     * @param  list<array{0: WidgetDefinition, 1: int}>  $row
     * @param  array<string, Placement>  $placements
     */
    private static function closeRow(array $row, int $usedColumns, int $y, array &$placements): int
    {
        if ($row === []) {
            return $y;
        }

        $last = count($row) - 1;
        $height = 0;
        foreach ($row as $index => [$definition, $x]) {
            $width = $definition->defaultW;
            if ($index === $last) {
                $width += min(self::COLUMNS - $usedColumns, $definition->maxW - $definition->defaultW);
            }
            $placements[$definition->code->value] = new Placement($x, $y, $width, $definition->defaultH);
            $height = max($height, $definition->defaultH);
        }

        return $y + $height;
    }
}
