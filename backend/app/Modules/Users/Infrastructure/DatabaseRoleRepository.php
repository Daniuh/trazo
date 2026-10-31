<?php

declare(strict_types=1);

// Roles con sus permisos, su política de MFA (mfa_policies) y cuántas personas los tienen, en pocas consultas.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Infrastructure;

use App\Modules\Users\Application\Data\RoleDetail;
use App\Modules\Users\Application\Data\RoleDraft;
use App\Modules\Users\Application\Data\RoleInfo;
use App\Modules\Users\Application\Ports\RoleRepository;
use App\Modules\Users\Domain\MfaPolicy;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class DatabaseRoleRepository implements RoleRepository
{
    public function all(): array
    {
        return $this->details($this->query());
    }

    public function find(string $code): ?RoleDetail
    {
        return $this->details($this->query()->where('roles.code', $code))[0] ?? null;
    }

    public function create(string $code, RoleDraft $draft): int
    {
        $now = now();
        $id = (int) DB::table('roles')->insertGetId([
            'code' => $code,
            'name' => $draft->name,
            'level' => $draft->level,
            'is_system' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->savePermissions($id, $draft->permissions);
        $this->savePolicy($id, $draft->mfa);

        return $id;
    }

    public function update(int $id, RoleDraft $draft): void
    {
        DB::table('roles')->where('id', $id)->update(['name' => $draft->name, 'level' => $draft->level, 'updated_at' => now()]);
        $this->savePermissions($id, $draft->permissions);
        $this->savePolicy($id, $draft->mfa);
    }

    public function delete(int $id): void
    {
        // Sus permisos y su política se van con él (CASCADE); el registro queda sin enlace (SET NULL)
        DB::table('roles')->where('id', $id)->delete();
    }

    public function pruneMemberOverrides(int $id): void
    {
        $members = fn (Builder $query) => $query->select('user_id')->from('user_role')->where('role_id', $id);
        $permissions = fn (Builder $query) => $query->select('permission_id')->from('role_permission')->where('role_id', $id);

        DB::table('user_permissions')->whereIn('user_id', $members)->where('effect', 'grant')->whereIn('permission_id', $permissions)->delete();
        DB::table('user_permissions')->whereIn('user_id', $members)->where('effect', 'deny')->whereNotIn('permission_id', $permissions)->delete();
    }

    private function query(): Builder
    {
        return DB::table('roles')
            ->leftJoin('mfa_policies', 'mfa_policies.role_id', '=', 'roles.id')
            ->select(['roles.id', 'roles.code', 'roles.name', 'roles.level', 'roles.is_system', 'mfa_policies.is_required', 'mfa_policies.grace_logins'])
            ->selectSub(fn (Builder $query) => $query->selectRaw('COUNT(*)')->from('user_role')->whereColumn('user_role.role_id', 'roles.id'), 'members')
            ->orderByDesc('roles.level');
    }

    /**
     * @return list<RoleDetail>
     */
    private function details(Builder $query): array
    {
        $rows = $query->get();
        $permissions = DB::table('role_permission')
            ->join('permissions', 'permissions.id', '=', 'role_permission.permission_id')
            ->whereIn('role_permission.role_id', $rows->pluck('id'))
            ->orderBy('permissions.code')
            ->get(['role_permission.role_id', 'permissions.code'])
            ->groupBy('role_id');

        return array_values($rows->map(fn (object $row): RoleDetail => new RoleDetail(
            new RoleInfo((int) $row->id, (string) $row->code, (string) $row->name, (int) $row->level),
            (bool) $row->is_system,
            array_values(($permissions[$row->id] ?? collect())->pluck('code')->map(fn (mixed $code): string => (string) $code)->all()),
            // Un rol sin fila de política no exige MFA
            new MfaPolicy((bool) ($row->is_required ?? false), (int) ($row->grace_logins ?? 0)),
            (int) $row->members,
        ))->all());
    }

    /**
     * @param  list<string>  $codes
     */
    private function savePermissions(int $id, array $codes): void
    {
        DB::table('role_permission')->where('role_id', $id)->delete();
        $ids = DB::table('permissions')->whereIn('code', $codes)->pluck('id');
        if ($ids->isNotEmpty()) {
            DB::table('role_permission')->insert($ids->map(fn (mixed $permission): array => [
                'role_id' => $id,
                'permission_id' => (int) $permission,
            ])->all());
        }
    }

    private function savePolicy(int $id, MfaPolicy $policy): void
    {
        $now = now();
        DB::table('mfa_policies')->upsert(
            [['role_id' => $id, 'is_required' => $policy->required, 'grace_logins' => $policy->graceLogins, 'created_at' => $now, 'updated_at' => $now]],
            ['role_id'],
            ['is_required', 'grace_logins', 'updated_at'],
        );
    }
}
