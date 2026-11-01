<?php

declare(strict_types=1);

// Reglas puras del módulo de sistema: umbrales de salud, estado general y huella de los eventos.
// Documentado en docs/backend/system.md

use App\Modules\Shared\Domain\EmailAddress;
use App\Modules\System\Domain\Fingerprint;
use App\Modules\System\Domain\HealthRules;
use App\Modules\System\Domain\HealthStatus;

it('interpreta tiempos, señales de vida, cola y disco', function (): void {
    expect(HealthRules::latency(null))->toBe(HealthStatus::Failing)
        ->and(HealthRules::latency(250.0))->toBe(HealthStatus::Warning)
        ->and(HealthRules::latency(4.0))->toBe(HealthStatus::Ok)
        ->and(HealthRules::heartbeat(null))->toBe(HealthStatus::Warning)
        ->and(HealthRules::heartbeat(121))->toBe(HealthStatus::Failing)
        ->and(HealthRules::heartbeat(60))->toBe(HealthStatus::Ok)
        ->and(HealthRules::queue(0, null, 1))->toBe(HealthStatus::Warning)
        ->and(HealthRules::queue(10, 400, 0))->toBe(HealthStatus::Warning)
        ->and(HealthRules::queue(10, 30, 0))->toBe(HealthStatus::Ok)
        ->and(HealthRules::disk(4, 100))->toBe(HealthStatus::Failing)
        ->and(HealthRules::disk(8, 100))->toBe(HealthStatus::Warning)
        ->and(HealthRules::disk(50, 100))->toBe(HealthStatus::Ok);
});

it('el estado general es el peor de sus componentes', function (): void {
    expect(HealthStatus::worst(HealthStatus::Ok, HealthStatus::Warning))->toBe(HealthStatus::Warning)
        ->and(HealthStatus::worst(HealthStatus::Warning, HealthStatus::Failing, HealthStatus::Ok))->toBe(HealthStatus::Failing)
        ->and(HealthStatus::worst(HealthStatus::Ok))->toBe(HealthStatus::Ok);
});

it('la huella depende del tipo y del origen', function (): void {
    expect(Fingerprint::of('job_failed', 'a'))->toBe(Fingerprint::of('job_failed', 'a'))
        ->not->toBe(Fingerprint::of('job_failed', 'b'))
        ->not->toBe(Fingerprint::of('service_down', 'a'));
});

it('enmascara un correo para los registros', function (): void {
    expect(EmailAddress::masked(' Daniel@Gmail.com '))->toBe('da***@gmail.com')
        ->and(EmailAddress::masked('a@b.co'))->toBe('a***@b.co')
        ->and(EmailAddress::masked('sin-arroba'))->toBe('si***');
});
