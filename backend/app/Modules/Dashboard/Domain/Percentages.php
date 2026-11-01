<?php

declare(strict_types=1);

// Porcentajes enteros que siempre suman 100 (método del mayor residuo): así una dona nunca dice 99 % o 101 %.
// Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Domain;

final class Percentages
{
    /**
     * @param  list<int>  $counts
     * @return list<int>
     */
    public static function of(array $counts): array
    {
        $total = array_sum($counts);
        if ($total === 0) {
            return array_fill(0, count($counts), 0);
        }

        $exact = array_map(fn (int $count): float => $count * 100 / $total, $counts);
        $result = array_map(fn (float $value): int => (int) floor($value), $exact);

        // Los puntos que faltan van a los de mayor parte decimal
        $remainders = [];
        foreach ($exact as $index => $value) {
            $remainders[$index] = $value - floor($value);
        }
        arsort($remainders);

        $missing = 100 - array_sum($result);
        foreach (array_keys($remainders) as $index) {
            if ($missing <= 0) {
                break;
            }
            $result[$index]++;
            $missing--;
        }

        return array_values($result);
    }
}
