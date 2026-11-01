<?php

declare(strict_types=1);

// Datos del diseño centrado en el riesgo (ADR-0081): pulso con el cumplimiento del plazo, mapa de riesgo, flujo de
// estados y balance del backlog. Salen de las fuentes en caché; cada cifra trae el filtro del listado al que lleva.
// Documentado en docs/backend/dashboard.md

namespace App\Modules\Dashboard\Application\Metrics;

use App\Modules\Dashboard\Application\Data\CachedMetric;
use App\Modules\Dashboard\Application\Data\MetricsContext;
use App\Modules\Incidents\Application\Data\PriorityInfo;
use App\Modules\Incidents\Application\Ports\IncidentCatalog;
use App\Modules\Incidents\Domain\DueBucket;
use App\Modules\Incidents\Domain\StatusFlags;
use DateTimeImmutable;
use DateTimeInterface;
use stdClass;

final readonly class RiskMetrics
{
    // Columnas del mapa: los grupos de vencimiento de los pendientes, en su orden
    private const RISK_BUCKETS = [DueBucket::Overdue, DueBucket::Today, DueBucket::Week, DueBucket::Later];

    public function __construct(
        private MetricSources $sources,
        private IncidentCatalog $catalog,
    ) {}

    public function pulse(MetricsContext $context): CachedMetric
    {
        $counts = $this->sources->counts($context);
        $overdue = $this->sources->overdue($context);
        $today = $this->sources->activity($context, 1);
        $onTime = $this->sources->onTime($context);

        $pending = $this->sources->pendingTotal($counts->value);
        $critical = $this->highestPriority();
        /** @var array{created: array<string, int>, resolved: array<string, int>} $activity */
        $activity = $today->value;
        /** @var array{resolved: int, on_time: int} $resolved */
        $resolved = $onTime->value;

        return new CachedMetric([
            // Todos los que la persona puede ver, en cualquier estado (lo pide la prueba)
            'total' => ['value' => array_sum(array_map('array_sum', $counts->value)), 'filter' => new stdClass],
            'pending' => ['value' => $pending, 'filter' => ['status' => $this->sources->pendingCodes()]],
            'overdue' => [
                'value' => $overdue->value,
                'share_of_pending' => $pending > 0 ? (int) round($overdue->value * 100 / $pending) : 0,
                'filter' => ['overdue' => true],
            ],
            'critical' => $critical === null ? null : [
                'name' => $critical->name,
                'value' => $this->pendingOf($counts->value, $critical),
                'filter' => ['status' => $this->sources->pendingCodes(), 'priority' => [$critical->code]],
            ],
            'today' => ['created' => array_sum($activity['created']), 'resolved' => array_sum($activity['resolved'])],
            // Sin resueltos en la ventana no hay porcentaje que mostrar
            'on_time' => [
                'days' => MetricSources::ON_TIME_DAYS,
                'resolved' => $resolved['resolved'],
                'percent' => $resolved['resolved'] > 0 ? (int) round($resolved['on_time'] * 100 / $resolved['resolved']) : null,
            ],
        ], min($counts->generatedAt, $overdue->generatedAt, $today->generatedAt, $onTime->generatedAt));
    }

    public function riskMap(MetricsContext $context): CachedMetric
    {
        $risk = $this->sources->risk($context);
        $counts = $this->sources->counts($context);
        /** @var array{matrix: array<int, array<int, int>>, now: string, end_of_today: string, end_of_week: string} $source */
        $source = $risk->value;
        $positions = array_flip(array_map(fn (DueBucket $bucket): string => $bucket->value, DueBucket::cases()));
        $pendingCodes = $this->sources->pendingCodes();

        // De la prioridad más alta a la más baja
        $rows = [];
        foreach (array_reverse(array_values($this->catalog->priorities())) as $priority) {
            $cells = [];
            foreach (self::RISK_BUCKETS as $bucket) {
                $cells[] = [
                    'bucket' => $bucket->value,
                    'count' => $source['matrix'][$priority->id][$positions[$bucket->value]] ?? 0,
                    'filter' => ['status' => $pendingCodes, 'priority' => [$priority->code], ...$this->range($bucket, $source)],
                ];
            }
            $rows[] = [
                'code' => $priority->code,
                'name' => $priority->name,
                'cells' => $cells,
                // Todos los de esa prioridad, en cualquier estado: el "por prioridad" que pide la prueba
                'total' => ['count' => $this->allOf($counts->value, $priority), 'filter' => ['priority' => [$priority->code]]],
            ];
        }

        return new CachedMetric([
            'columns' => array_map(fn (DueBucket $bucket): string => $bucket->value, self::RISK_BUCKETS),
            'rows' => $rows,
        ], min($risk->generatedAt, $counts->generatedAt));
    }

    /**
     * El camino son los estados en su orden; las salidas, los finales a los que se llega directo desde el inicial
     * (Cancelado). Así un catálogo distinto no exige tocar código.
     */
    public function flow(MetricsContext $context): CachedMetric
    {
        $counts = $this->sources->counts($context);
        $initial = $this->catalog->initialStatus();
        $exits = array_map(
            fn (StatusFlags $status): int => $status->id,
            array_filter($this->catalog->nextStatuses($initial->id), fn (StatusFlags $status): bool => $status->isFinal),
        );

        $stages = [];
        $exitItems = [];
        foreach ($this->catalog->statuses() as $status) {
            $item = [
                'code' => $status->code,
                'name' => $status->name,
                'count' => array_sum($counts->value[$status->id] ?? []),
                'filter' => ['status' => [$status->code]],
            ];
            if (in_array($status->id, $exits, true)) {
                $exitItems[] = $item;
            } else {
                $stages[] = $item;
            }
        }

        return new CachedMetric(['stages' => $stages, 'exits' => $exitItems], $counts->generatedAt);
    }

    /**
     * Pendientes de cada día, hacia atrás desde hoy: los del día anterior son los de hoy menos lo que entró hoy más lo
     * que salió hoy (resuelto o terminado sin resolverse). Es una aproximación: un reabierto cuenta según su estado actual.
     */
    public function backlog(MetricsContext $context, int $days): CachedMetric
    {
        $counts = $this->sources->counts($context);
        $activity = $this->sources->activity($context, $days);
        $exits = $this->sources->exits($context, $days);
        /** @var array{from: string, created: array<string, int>, resolved: array<string, int>} $flow */
        $flow = $activity->value;
        /** @var array<string, int> $cancelled */
        $cancelled = $exits->value;

        $first = new DateTimeImmutable($flow['from']);
        $dates = [];
        for ($i = 0; $i < $days; $i++) {
            $dates[] = $first->modify("+{$i} days")->format('Y-m-d');
        }

        $pending = $this->sources->pendingTotal($counts->value);
        $series = [];
        foreach (array_reverse($dates) as $date) {
            $created = $flow['created'][$date] ?? 0;
            $exited = ($flow['resolved'][$date] ?? 0) + ($cancelled[$date] ?? 0);
            $series[] = ['date' => $date, 'pending' => max(0, $pending), 'created' => $created, 'exited' => $exited];
            $pending = $pending - $created + $exited;
        }
        $series = array_reverse($series);
        $start = $series[0]['pending'] ?? 0;
        $end = $series[count($series) - 1]['pending'] ?? 0;

        return new CachedMetric(
            ['days' => $days, 'series' => $series, 'change' => $end - $start, 'filter' => ['status' => $this->sources->pendingCodes()]],
            min($counts->generatedAt, $activity->generatedAt, $exits->generatedAt),
        );
    }

    /**
     * Filtro de vencimiento de cada columna. El listado incluye el límite final, así que se corta un segundo antes.
     *
     * @param  array{now: string, end_of_today: string, end_of_week: string}  $window
     * @return array<string, mixed>
     */
    private function range(DueBucket $bucket, array $window): array
    {
        $before = fn (string $iso): string => (new DateTimeImmutable($iso))->modify('-1 second')->format(DateTimeInterface::ATOM);

        return match ($bucket) {
            DueBucket::Overdue => ['overdue' => true],
            DueBucket::Today => ['due_from' => $window['now'], 'due_to' => $before($window['end_of_today'])],
            DueBucket::Week => ['due_from' => $window['end_of_today'], 'due_to' => $before($window['end_of_week'])],
            default => ['due_from' => $window['end_of_week']],
        };
    }

    private function highestPriority(): ?PriorityInfo
    {
        $priorities = array_values($this->catalog->priorities());

        return $priorities === [] ? null : $priorities[count($priorities) - 1];
    }

    /**
     * @param  array<int, array<int, int>>  $counts
     */
    private function allOf(array $counts, PriorityInfo $priority): int
    {
        return array_sum(array_map(fn (array $byPriority): int => $byPriority[$priority->id] ?? 0, $counts));
    }

    /**
     * @param  array<int, array<int, int>>  $counts
     */
    private function pendingOf(array $counts, PriorityInfo $priority): int
    {
        $sum = 0;
        foreach ($this->sources->pendingStatusIds() as $statusId) {
            $sum += $counts[$statusId][$priority->id] ?? 0;
        }

        return $sum;
    }
}
