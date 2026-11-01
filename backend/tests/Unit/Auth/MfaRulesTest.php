<?php

declare(strict_types=1);

// Reglas del MFA sin base de datos: qué paso sigue al ingresar, la política más estricta y los códigos de recuperación.
// Documentado en docs/backend/auth.md

use App\Modules\Auth\Domain\Mfa\LoginStep;
use App\Modules\Auth\Domain\Mfa\MfaGate;
use App\Modules\Auth\Domain\Mfa\MfaRequirement;
use App\Modules\Auth\Domain\Mfa\RecoveryCode;

it('decide el paso siguiente del ingreso', function (bool $hasFactor, MfaRequirement $requirement, int $graceUsed, LoginStep $expected): void {
    expect(MfaGate::next($hasFactor, $requirement, $graceUsed))->toBe($expected);
})->with([
    'con MFA siempre pide el código' => [true, MfaRequirement::optional(), 0, LoginStep::Verify],
    'sin MFA ni obligación entra directo' => [false, MfaRequirement::optional(), 0, LoginStep::Complete],
    'obligatorio con gracia disponible lo sugiere' => [false, new MfaRequirement(true, 1), 0, LoginStep::SetupSuggested],
    'obligatorio sin gracia lo exige' => [false, new MfaRequirement(true, 1), 1, LoginStep::Setup],
    'obligatorio sin gracia configurada lo exige de una vez' => [false, new MfaRequirement(true, 0), 0, LoginStep::Setup],
]);

it('con varios roles aplica la política más estricta', function (): void {
    $strictest = MfaRequirement::strictest([
        new MfaRequirement(false, 5),
        new MfaRequirement(true, 3),
        new MfaRequirement(true, 1),
    ]);

    expect($strictest->required)->toBeTrue()->and($strictest->graceLogins)->toBe(1)
        ->and(MfaRequirement::strictest([])->required)->toBeFalse();
});

it('genera códigos de recuperación distintos, legibles y sin letras que se confunden', function (): void {
    $codes = RecoveryCode::batch(8);

    expect($codes)->toHaveCount(8)
        ->and(array_unique($codes))->toHaveCount(8)
        ->and(collect($codes)->every(fn (string $code): bool => preg_match('/^[A-HJKMNP-Z2-9]{5}-[A-HJKMNP-Z2-9]{5}$/', $code) === 1))->toBeTrue();
});

it('reconoce un código de recuperación escrito con minúsculas, espacios o sin guion', function (): void {
    expect(RecoveryCode::hash('abcde fghjk'))->toBe(RecoveryCode::hash('ABCDE-FGHJK'))
        ->and(RecoveryCode::hash('ABCDEFGHJK'))->toBe(RecoveryCode::hash('ABCDE-FGHJK'));
});
