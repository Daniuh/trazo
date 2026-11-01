<?php

declare(strict_types=1);

// Auditoría de administración en SQL: admin_actions con su tipo, quién lo hizo y la persona o el rol afectado, dentro
// del alcance de quien mira (AdminAuditScope traducido a SQL; una prueba compara los dos). Un rol eliminado ya no
// tiene fila: su código, su nombre y su nivel salen del detalle. Documentado en docs/backend/audit.md

namespace App\Modules\Audit\Infrastructure;

use App\Modules\Audit\Application\Data\AdminAuditCriteria;
use App\Modules\Audit\Application\Data\AdminAuditEntry;
use App\Modules\Audit\Application\Data\AuditPage;
use App\Modules\Audit\Application\Data\PersonRef;
use App\Modules\Audit\Application\Data\RoleRef;
use App\Modules\Audit\Application\Ports\AdminAuditQuery;
use App\Modules\Audit\Domain\AdminAuditScope;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class DatabaseAdminAudit implements AdminAuditQuery
{
    // Nivel guardado en el detalle de un rol eliminado; solo si es un número (al editar, el nivel va como "de → a")
    private const DETAIL_LEVEL = "JSON_TYPE(JSON_EXTRACT(a.details, '$.level')) = 'INTEGER' AND JSON_EXTRACT(a.details, '$.level') < ?";

    private const DETAIL_CODE = "JSON_UNQUOTE(JSON_EXTRACT(a.details, '$.code')) = ?";

    public function search(AdminAuditCriteria $criteria, AdminAuditScope $scope): AuditPage
    {
        $query = DB::table('admin_actions as a')
            ->join('admin_action_types as t', 't.id', '=', 'a.admin_action_type_id')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->leftJoin('users as tu', 'tu.id', '=', 'a.target_user_id')
            ->leftJoin('roles as r', 'r.id', '=', 'a.target_role_id')
            ->select([
                'a.id', 'a.created_at', 'a.details', 'a.ip_address', 'a.user_agent',
                't.code as type_code', 't.name as type_name', 'u.ulid as actor_id', 'u.name as actor_name',
                'tu.ulid as target_user_id', 'tu.name as target_user_name', 'r.code as role_code', 'r.name as role_name',
            ]);

        if (! $scope->everything) {
            $this->limit($query, $scope);
        }
        if (! $this->filter($query, $criteria)) {
            return new AuditPage([], null, $criteria->period);
        }

        AuditWindow::apply($query, 'a', $criteria->period, $criteria->cursor, $criteria->perPage);
        [$rows, $next] = AuditWindow::split($query->get(), $criteria->perPage);

        return new AuditPage(array_map($this->entry(...), $rows), $next, $criteria->period);
    }

    // Lo propio, y lo que toca a una persona o un rol de un nivel menor (el nivel de una persona es el de su rol más alto)
    private function limit(Builder $query, AdminAuditScope $scope): void
    {
        $query->where(fn (Builder $visible) => $visible
            ->where('a.user_id', $scope->viewerId)
            ->orWhere(fn (Builder $user) => $user
                ->whereNotNull('a.target_user_id')
                ->whereNotIn('a.target_user_id', DB::table('user_role')
                    ->join('roles', 'roles.id', '=', 'user_role.role_id')
                    ->where('roles.level', '>=', $scope->level)
                    ->select('user_role.user_id')))
            ->orWhereIn('a.target_role_id', DB::table('roles')->where('level', '<', $scope->level)->select('id'))
            ->orWhere(fn (Builder $deleted) => $deleted
                ->whereNull('a.target_user_id')
                ->whereNull('a.target_role_id')
                ->whereRaw(self::DETAIL_LEVEL, [$scope->level])));
    }

    // False si un filtro apunta a algo que no existe: entonces no hay nada que mostrar
    private function filter(Builder $query, AdminAuditCriteria $criteria): bool
    {
        foreach (['a.user_id' => $criteria->actor, 'a.target_user_id' => $criteria->user] as $column => $ulid) {
            if ($ulid === null) {
                continue;
            }
            $id = DB::table('users')->where('ulid', $ulid)->value('id');
            if ($id === null) {
                return false;
            }
            $query->where($column, $id);
        }

        if ($criteria->types !== []) {
            $typeIds = DB::table('admin_action_types')->whereIn('code', $criteria->types)->pluck('id')->all();
            if ($typeIds === []) {
                return false;
            }
            $query->whereIn('a.admin_action_type_id', $typeIds);
        }

        if ($criteria->role !== null) {
            // Un rol que ya no existe se encuentra por el código guardado en el detalle
            $roleId = DB::table('roles')->where('code', $criteria->role)->value('id');
            $query->where(fn (Builder $role) => $role
                ->when($roleId !== null, fn (Builder $current) => $current->orWhere('a.target_role_id', $roleId))
                ->orWhere(fn (Builder $deleted) => $deleted
                    ->whereNull('a.target_user_id')
                    ->whereNull('a.target_role_id')
                    ->whereRaw(self::DETAIL_CODE, [$criteria->role])));
        }

        return true;
    }

    private function entry(stdClass $row): AdminAuditEntry
    {
        $decoded = $row->details === null ? null : json_decode((string) $row->details, true, 512, JSON_THROW_ON_ERROR);
        $details = is_array($decoded) ? $decoded : null;

        return new AdminAuditEntry(
            id: (int) $row->id,
            createdAt: AuditWindow::date((string) $row->created_at),
            typeCode: (string) $row->type_code,
            typeName: (string) $row->type_name,
            actor: new PersonRef((string) $row->actor_id, (string) $row->actor_name),
            targetUser: $row->target_user_id === null ? null : new PersonRef((string) $row->target_user_id, (string) $row->target_user_name),
            targetRole: $this->role($row, $details),
            details: $details,
            ipAddress: $row->ip_address === null ? null : (string) $row->ip_address,
            userAgent: $row->user_agent === null ? null : (string) $row->user_agent,
        );
    }

    /**
     * @param  array<string, mixed>|null  $details
     */
    private function role(stdClass $row, ?array $details): ?RoleRef
    {
        if ($row->role_code !== null) {
            return new RoleRef((string) $row->role_code, (string) $row->role_name);
        }
        $code = $details['code'] ?? null;
        $name = $details['name'] ?? null;

        // Las acciones sobre roles guardan código y nombre; las de personas no tienen "code" en el detalle
        return $row->target_user_id === null && is_string($code)
            ? new RoleRef($code, is_string($name) ? $name : $code, deleted: true)
            : null;
    }
}
