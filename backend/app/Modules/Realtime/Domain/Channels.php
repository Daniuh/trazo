<?php

declare(strict_types=1);

// Canales privados del tiempo real y quién puede escucharlos. Es la misma regla de visibilidad del listado de
// incidentes: todos, los sin asignar, o los propios (lo que la persona creó o tiene asignado), más su canal personal.
// Documentado en docs/backend/realtime.md

namespace App\Modules\Realtime\Domain;

final class Channels
{
    public const ALL_INCIDENTS = 'incidents.all';

    public const UNASSIGNED_INCIDENTS = 'incidents.unassigned';

    // Canal personal: users.{ULID}
    public const PERSONAL_PREFIX = 'users.';

    public static function personal(string $userUlid): string
    {
        return self::PERSONAL_PREFIX.$userUlid;
    }

    /**
     * Si alguien puede escuchar un canal: el general y el de sin asignar según sus permisos; el personal, solo su dueño.
     *
     * @param  list<string>  $permissions
     */
    public static function allows(string $channel, string $userUlid, array $permissions): bool
    {
        return match (true) {
            $channel === self::ALL_INCIDENTS => in_array('incidents.view.any', $permissions, true),
            $channel === self::UNASSIGNED_INCIDENTS => in_array('incidents.view.unassigned', $permissions, true),
            default => $channel === self::personal($userUlid),
        };
    }

    /**
     * A qué canales se avisa un cambio de incidente: al general siempre; al de sin asignar si lo estaba antes o
     * después; y a quien lo creó y a quien estaba o queda asignado.
     *
     * @return list<string>
     */
    public static function forIncident(?string $creator, ?string $assigneeBefore, ?string $assigneeAfter, bool $wasUnassigned, bool $isUnassigned): array
    {
        $channels = [self::ALL_INCIDENTS];
        if ($wasUnassigned || $isUnassigned) {
            $channels[] = self::UNASSIGNED_INCIDENTS;
        }
        foreach ([$creator, $assigneeBefore, $assigneeAfter] as $person) {
            if ($person !== null) {
                $channels[] = self::personal($person);
            }
        }

        return array_values(array_unique($channels));
    }
}
