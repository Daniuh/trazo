<?php

declare(strict_types=1);

// Catálogos de la auditoría leídos de sus tablas: tipos de cada registro, roles, permisos y personas.
// Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Infrastructure;

use App\Modules\Audit\Application\Data\PersonRef;
use App\Modules\Audit\Application\Ports\AuditCatalogQuery;
use Illuminate\Support\Facades\DB;

final readonly class DatabaseAuditCatalog implements AuditCatalogQuery
{
    public function incidentTypes(): array
    {
        return $this->codes('activity_types', 'id');
    }

    public function adminTypes(): array
    {
        return $this->codes('admin_action_types', 'id');
    }

    public function roles(): array
    {
        return $this->codes('roles', 'level', descending: true);
    }

    public function permissions(): array
    {
        return $this->codes('permissions', 'code');
    }

    public function people(): array
    {
        return array_values(DB::table('users')->orderBy('name')->orderBy('id')->get(['ulid', 'name'])
            ->map(fn (object $user): PersonRef => new PersonRef((string) $user->ulid, (string) $user->name))
            ->all());
    }

    /**
     * @return list<array{code: string, name: string}>
     */
    private function codes(string $table, string $order, bool $descending = false): array
    {
        return array_values(DB::table($table)->orderBy($order, $descending ? 'desc' : 'asc')->get(['code', 'name'])
            ->map(fn (object $row): array => ['code' => (string) $row->code, 'name' => (string) $row->name])
            ->all());
    }
}
