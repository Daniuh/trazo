<?php

declare(strict_types=1);

// Quienes reparten el trabajo, leídos de la base con la misma regla de permisos efectivos de la sesión (PermissionSql:
// rol + concedidos − quitados). Solo cuentas activas. Documentado en docs/backend/notifications.md

namespace App\Modules\Notifications\Infrastructure;

use App\Modules\Notifications\Application\Ports\CoordinatorDirectory;
use App\Modules\Notifications\Domain\Coordinators;
use App\Modules\Users\Infrastructure\PermissionSql;
use Illuminate\Support\Facades\DB;

final readonly class DatabaseCoordinatorDirectory implements CoordinatorDirectory
{
    public function current(): Coordinators
    {
        return new Coordinators($this->with('incidents.assign'), $this->with('incidents.reopen.approve'));
    }

    /**
     * @return list<int>
     */
    private function with(string $permission): array
    {
        $query = DB::table('users')->where('users.is_active', true);
        PermissionSql::havingPermission($query, $permission);

        return array_values($query->orderBy('users.id')->pluck('users.id')->map(fn (mixed $id): int => (int) $id)->all());
    }
}
