<?php

declare(strict_types=1);

// Datos crudos del dashboard, cada uno en caché por alcance. Varios widgets comparten la misma fuente
// (total, pendientes, por estado y por prioridad salen de un solo conteo). Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application\Metrics;

use App\Modules\Dashboard\Application\Data\CachedMetric;
use App\Modules\Dashboard\Application\Data\DashboardSettings;
use App\Modules\Dashboard\Application\Data\DigestItem;
use App\Modules\Dashboard\Application\Data\IncidentDigest;
use App\Modules\Dashboard\Application\Data\MetricsContext;
use App\Modules\Dashboard\Application\Data\WorkloadRow;
use App\Modules\Dashboard\Application\Ports\DashboardQueries;
use App\Modules\Dashboard\Application\Ports\MetricsCache;
use App\Modules\Incidents\Application\Ports\IncidentCatalog;
use App\Modules\Incidents\Domain\StatusFlags;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final readonly class MetricSources
{
    public function __construct(
        private DashboardQueries $queries,
        private MetricsCache $cache,
        private IncidentCatalog $catalog,
        private DashboardSettings $settings,
    ) {}

    // [status_id][priority_id] => cantidad
    public function counts(MetricsContext $context): CachedMetric
    {
        return $this->cache->remember("counts:{$context->scopeKey}", fn (): array => $this->queries
            ->countsByStatusAndPriority($context->actor->id, $context->scope));
    }

    public function overdue(MetricsContext $context): CachedMetric
    {
        return $this->cache->remember("overdue:{$context->scopeKey}", fn (): int => $this->queries
            ->overdueCount($context->actor->id, $context->scope, $this->pendingStatusIds(), $context->now));
    }

    public function unassigned(MetricsContext $context): CachedMetric
    {
        return $this->cache->remember("unassigned:{$context->scopeKey}", fn (): int => $this->queries
            ->unassignedCount($context->actor->id, $context->scope, $this->pendingStatusIds()));
    }

    /**
     * Creados y resueltos por día, contados en la zona horaria del negocio (no en UTC).
     * El día de hoy entra en la clave, así la ventana avanza sola a medianoche.
     */
    public function activity(MetricsContext $context, int $days): CachedMetric
    {
        $zone = new DateTimeZone($this->settings->timezone);
        $today = $context->now->setTimezone($zone)->setTime(0, 0);
        $from = $today->modify('-'.($days - 1).' days');
        $key = "activity:{$context->scopeKey}:{$days}:{$today->format('Y-m-d')}";

        return $this->cache->remember($key, function () use ($context, $from, $zone): array {
            $fromUtc = $from->setTimezone(new DateTimeZone('UTC'));

            return [
                'from' => $from->format('Y-m-d'),
                'created' => $this->byDay($this->queries->countByHour($context->actor->id, $context->scope, 'created_at', $fromUtc), $zone),
                'resolved' => $this->byDay($this->queries->countByHour($context->actor->id, $context->scope, 'resolved_at', $fromUtc), $zone),
            ];
        });
    }

    public function dueSoon(MetricsContext $context): CachedMetric
    {
        return $this->cache->remember("due-soon:{$context->scopeKey}", function () use ($context): array {
            $until = $context->now->modify("+{$this->settings->dueSoonHours} hours");
            $digest = $this->queries->dueBetween($context->actor->id, $context->scope, $this->pendingStatusIds(), $context->now, $until, $this->settings->listLimit);

            return ['from' => $this->iso($context->now), 'to' => $this->iso($until), 'digest' => $this->plainDigest($digest)];
        });
    }

    // Depende de quién pregunta aunque vea todo, por eso la clave lleva su id
    public function assignedToMe(MetricsContext $context): CachedMetric
    {
        return $this->cache->remember("assigned:{$context->actor->id}:{$context->scopeKey}", fn (): array => [
            'user' => $this->queries->userUlid($context->actor->id),
            'digest' => $this->plainDigest($this->queries->assignedTo($context->actor->id, $context->scope, $this->pendingStatusIds(), $this->settings->listLimit)),
        ]);
    }

    public function workload(MetricsContext $context): CachedMetric
    {
        return $this->cache->remember("workload:{$context->scopeKey}", fn (): array => array_map(
            fn (WorkloadRow $row): array => ['ulid' => $row->ulid, 'name' => $row->name, 'pending' => $row->pending, 'overdue' => $row->overdue],
            $this->queries->workload($context->actor->id, $context->scope, $this->pendingStatusIds(), $context->now, $this->settings->workloadLimit),
        ));
    }

    /**
     * La caché solo guarda arreglos y valores simples: por seguridad, Laravel no vuelve a crear objetos al leerla.
     *
     * @return array{total: int, items: list<array{ulid: string, code: string, title: string, priority_id: int, status_id: int, due_date: string}>}
     */
    private function plainDigest(IncidentDigest $digest): array
    {
        return [
            'total' => $digest->total,
            'items' => array_map(fn (DigestItem $item): array => [
                'ulid' => $item->ulid,
                'code' => $item->code,
                'title' => $item->title,
                'priority_id' => $item->priorityId,
                'status_id' => $item->statusId,
                'due_date' => $this->iso($item->dueDate),
            ], $digest->items),
        ];
    }

    private function iso(DateTimeImmutable $date): string
    {
        return $date->setTimezone(new DateTimeZone('UTC'))->format(DateTimeInterface::ATOM);
    }

    /**
     * @return list<int>
     */
    public function pendingStatusIds(): array
    {
        return array_map(fn (StatusFlags $status): int => $status->id, $this->pendingStatuses());
    }

    /**
     * @return list<StatusFlags>
     */
    public function pendingStatuses(): array
    {
        return array_values(array_filter($this->catalog->statuses(), fn (StatusFlags $status): bool => $status->isPending));
    }

    /**
     * @param  array<string, int>  $hours  Cantidad por hora UTC
     * @return array<string, int> Cantidad por día local
     */
    private function byDay(array $hours, DateTimeZone $zone): array
    {
        $days = [];
        foreach ($hours as $hour => $count) {
            $day = (new DateTimeImmutable($hour, new DateTimeZone('UTC')))->setTimezone($zone)->format('Y-m-d');
            $days[$day] = ($days[$day] ?? 0) + $count;
        }

        return $days;
    }
}
