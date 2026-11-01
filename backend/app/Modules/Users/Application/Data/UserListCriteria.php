<?php

declare(strict_types=1);

// Qué pide el listado de usuarios: página, orden, búsqueda y filtros, ya validados.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Application\Data;

use App\Modules\Users\Domain\UserSort;

final readonly class UserListCriteria
{
    /**
     * @param  list<string>  $roles  Códigos de rol
     */
    public function __construct(
        public int $page,
        public int $perPage,
        public UserSort $sort,
        public bool $descending,
        public ?string $search,
        public array $roles,
        public ?bool $active,
        public ?bool $mfaEnabled,
    ) {}
}
