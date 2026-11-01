<?php

declare(strict_types=1);

// La ficha completa de una persona: sus datos, los permisos de su rol, sus excepciones y sus permisos efectivos,
// y cuántos incidentes pendientes tiene asignados (para avisar antes de desactivarla). Documentado en docs/backend/users.md

namespace App\Modules\Users\Application\Data;

final readonly class UserProfile
{
    /**
     * @param  list<string>  $rolePermissions
     * @param  list<string>  $grants
     * @param  list<string>  $denies
     * @param  list<string>  $permissions  Efectivos: rol + concedidos − quitados
     */
    public function __construct(
        public UserRecord $user,
        public array $rolePermissions,
        public array $grants,
        public array $denies,
        public array $permissions,
        public int $pendingAssignedIncidents,
    ) {}
}
