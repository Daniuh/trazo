<?php

declare(strict_types=1);

// Una página del listado de usuarios y cuántos hay en total con esos filtros. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application\Data;

final readonly class UserPage
{
    /**
     * @param  list<UserRecord>  $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $perPage,
    ) {}

    public function lastPage(): int
    {
        return max(1, (int) ceil($this->total / $this->perPage));
    }
}
