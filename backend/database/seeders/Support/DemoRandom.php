<?php

declare(strict_types=1);

// Azar de los datos de demostración con el generador de PHP y una semilla fija: los mismos datos en cada máquina.
// No usa Faker, que es dependencia de desarrollo y no viene en la imagen final. Documentado en docs/base-de-datos/seeders.md

namespace Database\Seeders\Support;

use Random\Engine\Mt19937;
use Random\Randomizer;

final class DemoRandom
{
    private static ?Randomizer $randomizer = null;

    public static function seed(int $seed): void
    {
        self::$randomizer = new Randomizer(new Mt19937($seed));
    }

    public static function numberBetween(int $min, int $max): int
    {
        return self::randomizer()->getInt($min, $max);
    }

    /** Verdadero con la probabilidad indicada, en porcentaje */
    public static function boolean(int $percent = 50): bool
    {
        return self::randomizer()->getInt(1, 100) <= $percent;
    }

    public static function randomFloat(int $decimals, float $min, float $max): float
    {
        return round(self::randomizer()->getFloat($min, $max), $decimals);
    }

    /**
     * @template T
     *
     * @param  non-empty-array<array-key, T>  $items
     * @return T
     */
    public static function randomElement(array $items): mixed
    {
        return $items[self::randomizer()->pickArrayKeys($items, 1)[0]];
    }

    /**
     * Varios elementos distintos, en su orden original.
     *
     * @template T
     *
     * @param  non-empty-array<array-key, T>  $items
     * @param  int<1, max>  $count
     * @return list<T>
     */
    public static function randomElements(array $items, int $count): array
    {
        return array_map(fn (int|string $key): mixed => $items[$key], self::randomizer()->pickArrayKeys($items, $count));
    }

    // Sin semilla explícita (por ejemplo, un seeder suelto en una prueba) también sirve, con la misma semilla de siempre
    private static function randomizer(): Randomizer
    {
        return self::$randomizer ??= new Randomizer(new Mt19937(2026));
    }
}
