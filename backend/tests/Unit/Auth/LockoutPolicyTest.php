<?php

declare(strict_types=1);

// Escalones del bloqueo progresivo.
// Documentado en docs/backend/auth.md

use App\Modules\Auth\Domain\LockoutPolicy;

$policy = fn (): LockoutPolicy => new LockoutPolicy([10 => 300, 5 => 60, 20 => 3600, 15 => 900]);

it('bloquea justo al llegar a cada escalón', function () use ($policy): void {
    expect($policy()->lockSecondsAfter(5))->toBe(60)
        ->and($policy()->lockSecondsAfter(10))->toBe(300)
        ->and($policy()->lockSecondsAfter(15))->toBe(900)
        ->and($policy()->lockSecondsAfter(20))->toBe(3600);
});

it('no bloquea entre escalones', function () use ($policy): void {
    foreach ([1, 4, 6, 9, 11, 19] as $failures) {
        expect($policy()->lockSecondsAfter($failures))->toBe(0);
    }
});

it('pasado el último escalón cada fallo vuelve a bloquear con la espera máxima', function () use ($policy): void {
    expect($policy()->lockSecondsAfter(21))->toBe(3600)
        ->and($policy()->lockSecondsAfter(50))->toBe(3600);
});

it('exige al menos un escalón', function (): void {
    new LockoutPolicy([]);
})->throws(InvalidArgumentException::class);
