<?php

declare(strict_types=1);

// Listado de usuarios en SQL: búsqueda por nombre o correo, filtros por rol, estado y MFA, orden de la lista blanca y
// página, con los roles cargados de una vez. Documentado en docs/backend/users.md

namespace App\Modules\Users\Infrastructure;

use App\Modules\Users\Application\Data\UserListCriteria;
use App\Modules\Users\Application\Data\UserPage;
use App\Modules\Users\Application\Ports\UserListQuery;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Builder;

final readonly class EloquentUserList implements UserListQuery
{
    public function __construct(private UserMapper $mapper) {}

    public function search(UserListCriteria $criteria): UserPage
    {
        $query = $this->mapper->query();
        $this->filter($query, $criteria);

        $total = (clone $query)->count();
        $items = $query
            ->orderBy("users.{$criteria->sort->value}", $criteria->descending ? 'desc' : 'asc')
            ->orderBy('users.id')
            ->forPage($criteria->page, $criteria->perPage)
            ->get();

        return new UserPage(
            array_values($items->map(fn (User $user) => $this->mapper->toRecord($user))->all()),
            $total,
            $criteria->page,
            $criteria->perPage,
        );
    }

    /**
     * @param  Builder<User>  $query
     */
    private function filter(Builder $query, UserListCriteria $criteria): void
    {
        if ($criteria->search !== null) {
            // Contiene, sin distinguir tildes ni mayúsculas (collation); los comodines escritos se toman literales
            $term = '%'.addcslashes($criteria->search, '%_\\').'%';
            $query->where(fn (Builder $any) => $any->where('users.name', 'like', $term)->orWhere('users.email', 'like', $term));
        }

        if ($criteria->roles !== []) {
            $query->whereHas('roles', fn (Builder $roles) => $roles->whereIn('roles.code', $criteria->roles));
        }

        if ($criteria->active !== null) {
            $query->where('users.is_active', $criteria->active);
        }

        if ($criteria->mfaEnabled !== null) {
            $query->whereRaw(($criteria->mfaEnabled ? '' : 'NOT ').UserMapper::MFA_ENABLED_SQL);
        }
    }
}
