<?php

declare(strict_types=1);

// Lectura y escritura de personas con Eloquent. Documentado en docs/backend/users.md

namespace App\Modules\Users\Infrastructure;

use App\Modules\Users\Application\Data\NewUser;
use App\Modules\Users\Application\Data\UserRecord;
use App\Modules\Users\Application\Ports\UserRepository;
use App\Modules\Users\Domain\RoleLevel;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use LogicException;

final readonly class EloquentUserRepository implements UserRepository
{
    public function __construct(private UserMapper $mapper) {}

    public function find(string $ulid): ?UserRecord
    {
        $user = $this->mapper->query()->where('users.ulid', strtolower($ulid))->first();

        return $user ? $this->mapper->toRecord($user) : null;
    }

    public function findById(int $id): ?UserRecord
    {
        $user = $this->mapper->query()->whereKey($id)->first();

        return $user ? $this->mapper->toRecord($user) : null;
    }

    public function create(NewUser $user, int $roleId): UserRecord
    {
        $model = User::query()->create([
            'name' => $user->name,
            'email' => $user->email,
            'password' => $this->unusablePassword(),
            'is_active' => true,
        ]);
        $model->roles()->attach($roleId);

        return $this->findById($model->id) ?? throw new LogicException('La cuenta recién creada no se encontró');
    }

    public function updateProfile(int $id, string $name, string $email): void
    {
        User::query()->whereKey($id)->firstOrFail()->update(['name' => $name, 'email' => $email]);
    }

    public function setActive(int $id, bool $active): void
    {
        User::query()->whereKey($id)->update(['is_active' => $active]);
    }

    public function setRole(int $id, int $roleId): void
    {
        User::query()->whereKey($id)->firstOrFail()->roles()->sync([$roleId]);
    }

    public function replaceOverrides(int $id, array $grants, array $denies, int $grantedBy): void
    {
        $wanted = [...array_fill_keys($grants, 'grant'), ...array_fill_keys($denies, 'deny')];
        $ids = DB::table('permissions')->whereIn('code', array_keys($wanted))->pluck('id', 'code');
        $current = DB::table('user_permissions')->where('user_id', $id)->pluck('effect', 'permission_id');

        // Se van las que ya no aplican o cambiaron de sentido; las que siguen igual conservan quién las dio
        $keep = [];
        foreach ($wanted as $code => $effect) {
            if (($current[$ids[$code]] ?? null) === $effect) {
                $keep[] = $ids[$code];
            }
        }
        DB::table('user_permissions')->where('user_id', $id)->whereNotIn('permission_id', $keep)->delete();

        $rows = [];
        foreach ($wanted as $code => $effect) {
            if (! in_array($ids[$code], $keep, true)) {
                $rows[] = ['user_id' => $id, 'permission_id' => $ids[$code], 'effect' => $effect, 'granted_by' => $grantedBy];
            }
        }
        if ($rows !== []) {
            DB::table('user_permissions')->insert($rows);
        }
    }

    public function overrides(int $id): array
    {
        $rows = DB::table('user_permissions')
            ->join('permissions', 'permissions.id', '=', 'user_permissions.permission_id')
            ->where('user_permissions.user_id', $id)
            ->orderBy('permissions.code')
            ->get(['permissions.code', 'user_permissions.effect']);

        return [
            'grants' => array_values($rows->where('effect', 'grant')->pluck('code')->map(fn (mixed $c): string => (string) $c)->all()),
            'denies' => array_values($rows->where('effect', 'deny')->pluck('code')->map(fn (mixed $c): string => (string) $c)->all()),
        ];
    }

    public function scramblePassword(int $id): void
    {
        User::query()->whereKey($id)->firstOrFail()->update(['password' => $this->unusablePassword()]);
    }

    public function lockActiveRoots(): int
    {
        return DB::table('users')
            ->where('users.is_active', true)
            ->whereExists(fn ($query) => $query->selectRaw('1')
                ->from('user_role')
                ->join('roles', 'roles.id', '=', 'user_role.role_id')
                ->whereColumn('user_role.user_id', 'users.id')
                ->where('roles.level', '>=', RoleLevel::ROOT))
            ->lockForUpdate()
            ->count();
    }

    public function pendingAssignedIncidents(int $id): int
    {
        return DB::table('incidents')
            ->join('statuses', 'statuses.id', '=', 'incidents.status_id')
            ->where('incidents.assigned_to', $id)
            ->where('statuses.is_pending', true)
            ->whereNull('incidents.deleted_at')
            ->count();
    }

    // Larga y aleatoria, y nadie la ve: la persona crea la suya con "¿Olvidaste tu contraseña?"
    private function unusablePassword(): string
    {
        return Str::password(64);
    }
}
