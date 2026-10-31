<?php

declare(strict_types=1);

// Historial de un incidente de demostración: cómo llegó a su estado actual. Asignación y cambios de estado que siguen
// solo las transiciones permitidas, con fechas en orden entre la creación y el último cambio, y quién hizo cada uno.
// Documentado en docs/base-de-datos/seeders.md

namespace Database\Seeders\Support;

use Illuminate\Support\Carbon;

final class IncidentStory
{
    // Recorridos posibles hasta cada estado, desde "Abierto". Una prueba los compara con status_transitions
    public const PATHS = [
        'open' => [[]],
        'in_progress' => [['in_progress']],
        'on_hold' => [['in_progress', 'on_hold']],
        'resolved' => [['in_progress', 'resolved'], ['in_progress', 'on_hold', 'in_progress', 'resolved']],
        'closed' => [['in_progress', 'resolved', 'closed'], ['in_progress', 'on_hold', 'in_progress', 'resolved', 'closed']],
        'cancelled' => [['cancelled'], ['in_progress', 'cancelled'], ['in_progress', 'on_hold', 'cancelled']],
    ];

    // Cuánto tarda, como máximo, en cerrarse algo ya resuelto (minutos)
    private const CLOSE_AFTER_MINUTES = 2 * 1440;

    // Lo que se cancela, se cancela pronto (minutos desde la creación)
    private const CANCEL_WITHIN_MINUTES = 3 * 1440;

    /**
     * Los pasos del historial después de "Creado", en orden.
     *
     * Quien atiende cambia el estado mientras trabaja; cerrar y cancelar lo hace quien supervisa. La asignación la hace
     * quien supervisa o la toma la propia persona.
     *
     * @param  array{status: string, created_at: Carbon, resolved_at: ?Carbon, assignee: ?int, now: Carbon}  $incident
     * @param  list<int>  $managers
     * @return list<array{type: string, user: int, at: Carbon, from?: string, to?: string, assignee?: int}>
     */
    public static function steps(array $incident, array $managers): array
    {
        /** @var list<string> $path */
        $path = fake()->randomElement(self::PATHS[$incident['status']]);
        $manager = (int) fake()->randomElement($managers);
        $assignee = $incident['assignee'];
        $times = self::times($incident, count($path) + ($assignee === null ? 0 : 1));
        $next = 0;

        $steps = [];
        if ($assignee !== null) {
            $assigner = fake()->boolean() ? $assignee : $manager;
            $steps[] = ['type' => 'assigned', 'user' => $assigner, 'at' => $times[$next++], 'assignee' => $assignee];
        }

        $from = 'open';
        foreach ($path as $to) {
            $byManager = in_array($to, ['closed', 'cancelled'], true) || $assignee === null;
            $steps[] = ['type' => 'status_changed', 'user' => $byManager ? $manager : $assignee, 'at' => $times[$next++], 'from' => $from, 'to' => $to];
            $from = $to;
        }

        return $steps;
    }

    /**
     * Momentos de los pasos, en orden. "Resuelto" cae justo en resolved_at y "Cerrado" llega después, sin pasar de ahora.
     *
     * @param  array{status: string, created_at: Carbon, resolved_at: ?Carbon, assignee: ?int, now: Carbon}  $incident
     * @return list<Carbon>
     */
    private static function times(array $incident, int $count): array
    {
        if ($count === 0) {
            return [];
        }

        $start = $incident['created_at'];
        $resolved = $incident['resolved_at'];
        $closed = $incident['status'] === 'closed';

        // Hasta dónde llegan los pasos de trabajo: la resolución; si se canceló, unos días después de crearse;
        // si sigue abierto, el presente
        $end = match (true) {
            $resolved !== null => $resolved,
            $incident['status'] === 'cancelled' => $start->copy()->addMinutes(self::CANCEL_WITHIN_MINUTES)->min($incident['now']),
            default => $incident['now'],
        };
        $working = $count - ($resolved !== null ? 1 : 0) - ($closed ? 1 : 0);

        $span = max(1, (int) $start->diffInMinutes($end));
        $offsets = [];
        for ($i = 0; $i < $working; $i++) {
            $offsets[] = fake()->numberBetween(0, $span - 1);
        }
        sort($offsets);
        $times = array_map(fn (int $minutes): Carbon => $start->copy()->addMinutes($minutes), $offsets);

        if ($resolved !== null) {
            $times[] = $resolved->copy();
        }
        if ($closed && $resolved !== null) {
            $limit = max(1, min(self::CLOSE_AFTER_MINUTES, (int) $resolved->diffInMinutes($incident['now']) - 1));
            $times[] = $resolved->copy()->addMinutes(fake()->numberBetween(1, $limit));
        }

        return $times;
    }
}
