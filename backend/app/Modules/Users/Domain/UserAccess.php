<?php

declare(strict_types=1);

// Única fuente de quién puede administrar a quién. La usan los casos de uso y el campo "actions" de la API, así el
// front nunca calcula reglas. Solo se administra hacia abajo en la jerarquía; la cima administra a todos menos a sí
// misma. Documentado en docs/backend/users.md

namespace App\Modules\Users\Domain;

final class UserAccess
{
    /** Null si puede; si no, el motivo. */
    public function denial(UserAction $action, Administrator $admin, ManagedUser $user): ?UserDenial
    {
        if (! $admin->can($action->permission())) {
            return UserDenial::Forbidden;
        }

        // Sus propios datos sí los corrige; lo demás se lo cambia otra persona
        if ($user->id === $admin->id) {
            return $action === UserAction::Update ? null : UserDenial::Self;
        }

        if (! $admin->outranks($user->level)) {
            return UserDenial::Outranked;
        }

        return match ($action) {
            UserAction::Activate => $user->isActive ? UserDenial::AlreadyActive : null,
            UserAction::Deactivate => $user->isActive ? null : UserDenial::AlreadyInactive,
            UserAction::GrantPermissions => $user->level >= RoleLevel::ROOT ? UserDenial::RootHasEverything : null,
            UserAction::ResetMfa => $user->mfaEnabled ? null : UserDenial::MfaNotEnabled,
            UserAction::Update, UserAction::AssignRole, UserAction::ResetAccess => null,
        };
    }

    /**
     * Todas las acciones evaluadas de una vez, para el campo "actions" de la API.
     *
     * @return array<string, bool>
     */
    public function actions(Administrator $admin, ManagedUser $user): array
    {
        $actions = [];
        foreach (UserAction::cases() as $action) {
            $actions[$action->value] = $this->denial($action, $admin, $user) === null;
        }

        return $actions;
    }

    // Un rol se da solo si está por debajo de quien lo da (o si es la cima)
    public function canGiveRole(Administrator $admin, int $roleLevel): bool
    {
        return $admin->outranks($roleLevel);
    }
}
