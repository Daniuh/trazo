<?php

declare(strict_types=1);

// Forma pública de la pantalla de sistema: salud, eventos, tipos y jobs fallidos. Fechas en ISO 8601 UTC.
// Documentado en docs/backend/system.md

namespace App\Modules\System\Http;

use App\Modules\System\Application\Data\ComponentHealth;
use App\Modules\System\Application\Data\EventRecord;
use App\Modules\System\Application\Data\EventType;
use App\Modules\System\Application\Data\FailedJob;
use App\Modules\System\Application\Data\HealthReport;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final class SystemResource
{
    /**
     * @return array<string, mixed>
     */
    public function health(HealthReport $report): array
    {
        return [
            'checked_at' => self::date($report->checkedAt),
            'status' => $report->status->value,
            'components' => array_map(fn (ComponentHealth $component): array => [
                'code' => $component->code,
                'status' => $component->status->value,
                ...$component->details,
            ], $report->components),
            'maintenance' => $report->maintenance === null ? null : [
                'since' => self::date($report->maintenance->since),
                'enabled_by' => $report->maintenance->enabledByName,
            ],
            'app' => [
                'environment' => app()->environment(),
                'laravel' => app()->version(),
                'php' => PHP_VERSION,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function event(EventRecord $event): array
    {
        return [
            'id' => $event->id,
            'type' => $this->type($event->type),
            'message' => $event->message,
            'context' => $event->context === [] ? null : $event->context,
            'occurrences' => $event->occurrences,
            'first_seen_at' => self::date($event->firstSeenAt),
            'last_seen_at' => self::date($event->lastSeenAt),
            'trace_id' => $event->traceId,
            'status' => $event->status->value,
            'status_changed_by' => $event->changedByName,
            'status_changed_at' => $event->changedAt === null ? null : self::date($event->changedAt),
        ];
    }

    /**
     * @return array{code: string, name: string, category: string, severity: string}
     */
    public function type(EventType $type): array
    {
        return ['code' => $type->code, 'name' => $type->name, 'category' => $type->category, 'severity' => $type->severity];
    }

    /**
     * @return array<string, string>
     */
    public function failedJob(FailedJob $job): array
    {
        return [
            'id' => $job->id,
            'job' => $job->job,
            'queue' => $job->queue,
            'failed_at' => self::date($job->failedAt),
            'error' => $job->error,
        ];
    }

    public static function date(DateTimeImmutable $date): string
    {
        return $date->setTimezone(new DateTimeZone('UTC'))->format(DateTimeInterface::ATOM);
    }
}
