<?php

declare(strict_types=1);

// Quién puede recibir incidentes: cuentas activas cuyo rol incluye atender incidentes asignados.
// Se decide por permiso, nunca por nombre de rol. Documentado en docs/backend/incidents.md

namespace App\Modules\Incidents\Infrastructure;

use App\Modules\Incidents\Application\Data\Person;
use App\Modules\Incidents\Application\Ports\AssigneeDirectory;
use App\Modules\Users\Infrastructure\Models\User;
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
        return array_values($this->eligible()->orderBy('users.name')->get()
            ->map(fn (User $user): Person => new Person($user->id, $user->ulid, $user->name))
            ->all());
    }

    /**
     * @return Builder<User>
     */
    private function eligible(): Builder
    {
        return User::query()
            ->select(['users.id', 'users.ulid', 'users.name'])
            ->where('users.is_active', true)
            ->whereExists(fn ($query) => $query->selectRaw('1')
                ->from('user_role')
                ->join('role_permission', 'role_permission.role_id', '=', 'user_role.role_id')
                ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
                ->whereColumn('user_role.user_id', 'users.id')
                ->where('permissions.code', self::WORK_PERMISSION));
    }
}
