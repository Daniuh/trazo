<?php

declare(strict_types=1);

// Arma los datos de cada widget a partir de las fuentes en caché: nombres de catálogo, porcentajes y el filtro del
// listado al que lleva cada cifra (así el front no decide qué es "pendiente"). Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application\Metrics;

use App\Modules\Dashboard\Application\Data\CachedMetric;
use App\Modules\Dashboard\Application\Data\MetricsContext;
use App\Modules\Dashboard\Domain\Percentages;
use App\Modules\Dashboard\Domain\WidgetCode;
use App\Modules\Incidents\Application\Data\PriorityInfo;
use App\Modules\Incidents\Application\Ports\IncidentCatalog;
use App\Modules\Incidents\Domain\IncidentRules;
use App\Modules\Incidents\Domain\StatusFlags;
use DateTimeImmutable;
use stdClass;

final readonly class WidgetMetrics
{
    // Valor de "sin asignar" en los filtros del listado
    private const UNASSIGNED = 'none';

    public function __construct(
        private MetricSources $sources,
        private IncidentCatalog $catalog,
        private IncidentRules $rules,
    ) {}

    public function build(WidgetCode $code, MetricsContext $context, int $trendDays): CachedMetric
    {
        return match ($code) {
            WidgetCode::TotalIncidents => $this->fromCounts($context, fn (array $counts): array => [
                'value' => $this->total($counts),
                'filter' => new stdClass,
            ]),
            WidgetCode::PendingIncidents => $this->fromCounts($context, fn (array $counts): array => [
                'value' => $this->pendingTotal($counts),
                'filter' => ['status' => $this->pendingCodes()],
            ]),
            WidgetCode::OverdueIncidents => $this->overdue($context),
            WidgetCode::UnassignedIncidents => $this->map($this->sources->unassigned($context), fn (int $value): array => [
                'value' => $value,
                'filter' => ['status' => $this->pendingCodes(), 'assignee' => [self::UNASSIGNED]],
            ]),
            WidgetCode::IncidentsByStatus => $this->fromCounts($context, fn (array $counts): array => $this->byStatus($counts)),
            WidgetCode::IncidentsByPriority => $this->fromCounts($context, fn (array $counts): array => $this->byPriority($counts)),
            WidgetCode::CreatedVsResolved => $this->map($this->sources->activity($context, $trendDays), fn (array $activity): array => $this->trend($activity, $trendDays)),
            WidgetCode::DueSoon => $this->map($this->sources->dueSoon($context), fn (array $source): array => [
                ...$this->digest($source['digest'], $context->now),
                'filter' => ['status' => $this->pendingCodes(), 'due_from' => $source['from'], 'due_to' => $source['to']],
            ]),
            WidgetCode::AssignedToMe => $this->map($this->sources->assignedToMe($context), fn (array $source): array => [
                ...$this->digest($source['digest'], $context->now),
                'filter' => ['status' => $this->pendingCodes(), 'assignee' => [$source['user']]],
            ]),
            WidgetCode::WorkloadByAgent => $this->map($this->sources->workload($context), fn (array $rows): array => [
                'items' => array_map(fn (array $row): array => [
                    'id' => $row['ulid'],
                    'name' => $row['name'],
                    'pending' => $row['pending'],
                    'overdue' => $row['overdue'],
                    'filter' => ['status' => $this->pendingCodes(), 'assignee' => [$row['ulid']]],
                ], $rows),
            ]),
        };
    }

    private function overdue(MetricsContext $context): CachedMetric
    {
        $counts = $this->sources->counts($context);
        $overdue = $this->sources->overdue($context);
        $pending = $this->pendingTotal($counts->value);

        return new CachedMetric([
            'value' => $overdue->value,
            // Qué parte de lo pendiente ya venció
            'share_of_pending' => $pending > 0 ? (int) round($overdue->value * 100 / $pending) : 0,
            'filter' => ['overdue' => true],
        ], min($counts->generatedAt, $overdue->generatedAt));
    }

    /**
     * @param  array<int, array<int, int>>  $counts
     * @return array<string, mixed>
     */
    private function byStatus(array $counts): array
    {
        $items = array_map(fn (StatusFlags $status): array => [
            'code' => $status->code,
            'name' => $status->name,
            'count' => array_sum($counts[$status->id] ?? []),
            'filter' => ['status' => [$status->code]],
        ], array_values($this->catalog->statuses()));

        return ['total' => $this->total($counts), 'items' => $items];
    }

    /**
     * @param  array<int, array<int, int>>  $counts
     * @return array<string, mixed>
     */
    private function byPriority(array $counts): array
    {
        $priorities = array_values($this->catalog->priorities());
        $values = array_map(function (PriorityInfo $priority) use ($counts): int {
            $sum = 0;
            foreach ($counts as $byPriority) {
                $sum += $byPriority[$priority->id] ?? 0;
            }

            return $sum;
        }, $priorities);
        $percents = Percentages::of($values);

        $items = [];
        foreach ($priorities as $index => $priority) {
            $items[] = [
                'code' => $priority->code,
                'name' => $priority->name,
                'count' => $values[$index],
                'percent' => $percents[$index],
                'filter' => ['priority' => [$priority->code]],
            ];
        }

        return ['total' => array_sum($values), 'items' => $items];
    }

    /**
     * Una entrada por día, también los días sin movimiento.
     *
     * @param  array<string, mixed>  $activity  Fuente "activity": from, created y resolved por día
     * @return array<string, mixed>
     */
    private function trend(array $activity, int $days): array
    {
        /** @var array{from: string, created: array<string, int>, resolved: array<string, int>} $activity */
        $day = new DateTimeImmutable($activity['from']);
        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $day->modify("+{$i} days")->format('Y-m-d');
            $series[] = [
                'date' => $date,
                'created' => $activity['created'][$date] ?? 0,
                'resolved' => $activity['resolved'][$date] ?? 0,
            ];
        }

        return [
            'days' => $days,
            'series' => $series,
            'totals' => [
                'created' => array_sum(array_column($series, 'created')),
                'resolved' => array_sum(array_column($series, 'resolved')),
            ],
        ];
    }

    /**
     * @param  array{total: int, items: list<array{ulid: string, code: string, title: string, priority_id: int, status_id: int, due_date: string}>}  $digest
     * @return array{total: int, items: list<array<string, mixed>>}
     */
    private function digest(array $digest, DateTimeImmutable $now): array
    {
        $statuses = $this->statusesById();
        $priorities = $this->prioritiesById();
        // Un valor retirado del catálogo no se puede nombrar: esa fila se omite de la lista corta
        $known = array_values(array_filter(
            $digest['items'],
            fn (array $item): bool => isset($statuses[$item['status_id']], $priorities[$item['priority_id']]),
        ));

        return [
            'total' => $digest['total'],
            'items' => array_map(function (array $item) use ($statuses, $priorities, $now): array {
                $status = $statuses[$item['status_id']];
                $priority = $priorities[$item['priority_id']];

                return [
                    'id' => $item['ulid'],
                    'code' => $item['code'],
                    'title' => $item['title'],
                    'priority' => ['code' => $priority->code, 'name' => $priority->name],
                    'status' => ['code' => $status->code, 'name' => $status->name],
                    'due_date' => $item['due_date'],
                    'is_overdue' => $this->rules->isOverdue($status, new DateTimeImmutable($item['due_date']), $now),
                ];
            }, $known),
        ];
    }

    /**
     * @param  callable(array<int, array<int, int>>): array<string, mixed>  $shape
     */
    private function fromCounts(MetricsContext $context, callable $shape): CachedMetric
    {
        return $this->map($this->sources->counts($context), $shape);
    }

    private function map(CachedMetric $metric, callable $shape): CachedMetric
    {
        return new CachedMetric($shape($metric->value), $metric->generatedAt);
    }

    /**
     * @param  array<int, array<int, int>>  $counts
     */
    private function total(array $counts): int
    {
        return array_sum(array_map('array_sum', $counts));
    }

    /**
     * @param  array<int, array<int, int>>  $counts
     */
    private function pendingTotal(array $counts): int
    {
        $sum = 0;
        foreach ($this->sources->pendingStatusIds() as $statusId) {
            $sum += array_sum($counts[$statusId] ?? []);
        }

        return $sum;
    }

    /**
     * @return list<string>
     */
    private function pendingCodes(): array
    {
        return array_map(fn (StatusFlags $status): string => $status->code, $this->sources->pendingStatuses());
    }

    /**
     * @return array<int, StatusFlags>
     */
    private function statusesById(): array
    {
        $byId = [];
        foreach ($this->catalog->statuses() as $status) {
            $byId[$status->id] = $status;
        }

        return $byId;
    }

    /**
     * @return array<int, PriorityInfo>
     */
    private function prioritiesById(): array
    {
        $byId = [];
        foreach ($this->catalog->priorities() as $priority) {
            $byId[$priority->id] = $priority;
        }

        return $byId;
    }
}
