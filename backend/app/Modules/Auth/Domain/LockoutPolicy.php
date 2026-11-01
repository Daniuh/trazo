<?php

declare(strict_types=1);

// Bloqueo progresivo: cuántos segundos espera alguien según los fallos seguidos que lleva.
// Documentado en docs/backend/auth.md

namespace App\Modules\Auth\Domain;

use InvalidArgumentException;

final readonly class LockoutPolicy
{
    /** @var array<int, int> Fallos => segundos, de menor a mayor */
    private array $steps;

    /**
     * @param  array<int, int>  $steps  Ej.: [5 => 60, 10 => 300]
     */
    public function __construct(array $steps)
    {
        if ($steps === []) {
            throw new InvalidArgumentException('La política de bloqueo necesita al menos un escalón.');
        }

        ksort($steps);
        $this->steps = $steps;
    }

    /**
     * Bloquea justo al llegar a cada escalón. Pasado el último, cada fallo vuelve a bloquear con la espera máxima.
     */
    public function lockSecondsAfter(int $failures): int
    {
        if (isset($this->steps[$failures])) {
            return $this->steps[$failures];
        }

        $lastThreshold = array_key_last($this->steps);

        return $failures > $lastThreshold ? $this->steps[$lastThreshold] : 0;
    }
}
