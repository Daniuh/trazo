<?php

declare(strict_types=1);

// Forma de una persona en la API: resumen para el listado y ficha completa con permisos y las acciones que quien
// administra puede hacer con ella. Nunca sale el id interno. Documentado en docs/backend/users.md

namespace App\Modules\Users\Http;

use App\Modules\Users\Application\Data\RoleInfo;
use App\Modules\Users\Application\Data\UserProfile;
use App\Modules\Users\Application\Data\UserRecord;
use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Domain\UserAccess;
use DateTimeInterface;

final readonly class UserResource
{
    public function __construct(private UserAccess $access) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(UserRecord $user): array
    {
        return [
            'id' => $user->ulid,
            'name' => $user->name,
            'email' => $user->email,
            'is_active' => $user->isActive,
            'role' => $this->role($user->role()),
            'mfa_enabled' => $user->mfaEnabled,
            'last_login_at' => $user->lastLoginAt?->format(DateTimeInterface::ATOM),
            'created_at' => $user->createdAt->format(DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(UserProfile $profile, Administrator $admin): array
    {
        return [
            ...$this->summary($profile->user),
            'permissions' => [
                'effective' => $profile->permissions,
                'role' => $profile->rolePermissions,
                'granted' => $profile->grants,
                'denied' => $profile->denies,
            ],
            'pending_assigned_incidents' => $profile->pendingAssignedIncidents,
            'is_self' => $profile->user->id === $admin->id,
            'actions' => $this->access->actions($admin, $profile->user->managed()),
        ];
    }

    /**
     * @return array{code: string, name: string, level: int}|null
     */
    public function role(?RoleInfo $role): ?array
    {
        return $role === null ? null : ['code' => $role->code, 'name' => $role->name, 'level' => $role->level];
    }
}
