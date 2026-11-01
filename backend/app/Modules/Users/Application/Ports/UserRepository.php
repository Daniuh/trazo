<?php

declare(strict_types=1);

// Lectura y escritura de las personas que administra el módulo. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application\Ports;

use App\Modules\Users\Application\Data\NewUser;
use App\Modules\Users\Application\Data\UserRecord;

interface UserRepository
{
    public function find(string $ulid): ?UserRecord;

    public function findById(int $id): ?UserRecord;

    // Crea la cuenta con una contraseña aleatoria que nadie conoce; la persona crea la suya desde el correo
    public function create(NewUser $user, int $roleId): UserRecord;

    public function updateProfile(int $id, string $name, string $email): void;

    public function setActive(int $id, bool $active): void;

    // Reemplaza su rol por este
    public function setRole(int $id, int $roleId): void;

    /**
     * Deja exactamente estas excepciones de permisos. Las que ya tenía conservan quién las dio.
     *
     * @param  list<string>  $grants
     * @param  list<string>  $denies
     */
    public function replaceOverrides(int $id, array $grants, array $denies, int $grantedBy): void;

    /**
     * @return array{grants: list<string>, denies: list<string>}
     */
    public function overrides(int $id): array;

    // Cambia la contraseña por una aleatoria que nadie conoce: la anterior deja de servir
    public function scramblePassword(int $id): void;

    // Cuántas personas activas están en la cima de la jerarquía, bloqueándolas hasta el final de la transacción
    public function lockActiveRoots(): int;

    public function pendingAssignedIncidents(int $id): int;
}
