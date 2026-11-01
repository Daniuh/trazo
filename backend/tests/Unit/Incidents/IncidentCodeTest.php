<?php

declare(strict_types=1);

// Formato del código de incidente y lo que se reconoce cuando una persona lo escribe.
// Documentado en docs/backend/incidents.md

use App\Modules\Incidents\Domain\IncidentCode;

it('arma prefijo, año en dos cifras y número con al menos cuatro', function (): void {
    expect(IncidentCode::format('tz', 2026, 142))->toBe('TZ-26-0142')
        ->and(IncidentCode::format('TZ', 2030, 12345))->toBe('TZ-30-12345');
});

it('reconoce el código con o sin prefijo, guiones y ceros', function (): void {
    foreach (['TZ-26-0142', 'tz-26-142', 'TZ26-142', '26-0142', ' 26-142 '] as $typed) {
        expect(IncidentCode::normalize($typed, 'TZ'))->toBe('TZ-26-0142');
    }
});

it('no confunde un número suelto ni el formato anterior con un código', function (): void {
    foreach (['142', '260142', 'INC-000142', '26-0', 'impresora'] as $typed) {
        expect(IncidentCode::looksLike($typed))->toBeFalse();
    }
});
