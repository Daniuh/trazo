<?php

declare(strict_types=1);

// A quién se avisa de cada cosa. Nadie recibe aviso de lo que hizo él mismo, y cada persona recibe un solo aviso por
// hecho (el más importante para ella).
// - Mensaje nuevo: a quien creó el incidente, a quien lo tiene asignado y a quienes ya escribieron en la conversación.
// - Asignado: a la persona que lo recibe.
// - Creado: a quien quedó asignado; si quedó sin asignar, a quienes pueden asignar; si es crítico, a quienes pueden
//   asignar y a quien lo atiende (ADR-0085).
// - Cambio de estado: a quien lo creó y a quien lo tiene asignado.
// - Reapertura pedida: a quienes pueden aprobarla.
// - Reabierto: a creador, asignado y a quien pidió la reapertura.
// - Reapertura rechazada: a quien la pidió.
// - Vencido: a quien lo atiende y a quienes pueden asignar.
// Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Domain;

final class Recipients
{
    // Registros del historial que avisan a quienes coordinan: solo para estos hace falta buscarlos
    public const COORDINATION_ACTIVITIES = ['created', 'reopen_requested'];

    // Clave natural de la prioridad que avisa a quienes coordinan al crear el incidente
    public const CRITICAL_PRIORITY = 'critical';

    /**
     * @return list<int>
     */
    public static function forComment(int $author, Participants $people): array
    {
        return self::except($author, [$people->creator, $people->assignee, ...$people->commenters]);
    }

    /**
     * Destinatarios de un registro del historial, según su tipo (código de activity_types), con el aviso de cada uno.
     *
     * @return array<int, NotificationType>
     */
    public static function forActivity(string $activity, int $actor, Participants $people, ?Coordinators $coordinators = null): array
    {
        $coordinators ??= new Coordinators;

        return match ($activity) {
            'created' => self::forCreation($actor, $people, $coordinators),
            'assigned' => self::to($actor, [$people->assignee], NotificationType::IncidentAssigned),
            'status_changed' => self::to($actor, [$people->creator, $people->assignee], NotificationType::StatusChanged),
            'reopen_requested' => self::to($actor, $coordinators->reopenApprovers, NotificationType::ReopenRequested),
            'reopened' => self::to($actor, [$people->creator, $people->assignee, $people->reopenRequester], NotificationType::IncidentReopened),
            'reopen_rejected' => self::to($actor, [$people->reopenRequester], NotificationType::ReopenRejected),
            default => [],
        };
    }

    /**
     * Un incidente que venció sin resolverse. No hay quien actúe: el aviso lo da el sistema.
     *
     * @return list<int>
     */
    public static function forOverdue(?int $assignee, Coordinators $coordinators): array
    {
        return self::except(0, [$assignee, ...$coordinators->assigners]);
    }

    /**
     * Si alguien califica para varios avisos, recibe el más importante: crítico, luego asignado, luego sin asignar.
     *
     * @return array<int, NotificationType>
     */
    private static function forCreation(int $actor, Participants $people, Coordinators $coordinators): array
    {
        $users = $people->assignee === null && ! $people->critical
            ? self::to($actor, $coordinators->assigners, NotificationType::UnassignedCreated)
            : [];
        $users = self::to($actor, [$people->assignee], NotificationType::IncidentAssigned) + $users;

        if ($people->critical) {
            $users = self::to($actor, [...$coordinators->assigners, $people->assignee], NotificationType::CriticalCreated) + $users;
        }

        return $users;
    }

    /**
     * @param  list<int|null>  $people
     * @return array<int, NotificationType>
     */
    private static function to(int $actor, array $people, NotificationType $type): array
    {
        $users = self::except($actor, $people);

        return $users === [] ? [] : array_fill_keys($users, $type);
    }

    /**
     * @param  list<int|null>  $people
     * @return list<int>
     */
    private static function except(int $actor, array $people): array
    {
        return array_values(array_unique(array_filter($people, fn (?int $person): bool => $person !== null && $person !== $actor)));
    }
}
