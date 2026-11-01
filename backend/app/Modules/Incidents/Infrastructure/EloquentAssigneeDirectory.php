<?php

declare(strict_types=1);

// Quién puede recibir incidentes: cuentas activas con el permiso de atender incidentes asignados (por su rol o
// concedido a la persona, y sin que se lo hayan quitado).
// Se decide por permiso, nunca por nombre de rol. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\Data\Person;
use App\Modules\Incidents\Application\Ports\AssigneeDirectory;
use App\Modules\Users\Infrastructure\Models\User;
use App\Modules\Users\Infrastructure\PermissionSql;
use Illuminate\Database\Eloquent\Builder;

final class EloquentAssigneeDirectory implements AssigneeDirectory
{
    private const WORK_PERMISSION = 'incidents.status.assigned';

    public function find(string $ulid): ?Person
    {
        $user = $this->eligible()->where('users.ulid', $ulid)->first();

        return $user ? new Person($user->id, $user->ulid, $user->name) : null;
    }

    public function all(): array
    {
        return array_values($this->eligible()->orderBy('users.family_names')->orderBy('users.given_names')->get()
            ->map(fn (User $user): Person => new Person($user->id, $user->ulid, $user->name))
            ->all());
    }

    /**
     * @return Builder<User>
     */
    private function eligible(): Builder
    {
        $query = User::query()
            ->select(['users.id', 'users.ulid', 'users.name'])
            ->where('users.is_active', true);

        PermissionSql::havingPermission($query, self::WORK_PERMISSION);

        return $query;
    }
}
