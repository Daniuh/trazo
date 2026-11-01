<?php

declare(strict_types=1);

// Endpoints del acceso de una persona: rol, permisos, activar o desactivar, y restablecer su MFA o su acceso.
// Todos responden con la ficha actualizada. Documentado en docs/backend/users.md

namespace App\Modules\Users\Http;

use App\Modules\Users\Application\ChangeUserPermissions;
use App\Modules\Users\Application\ChangeUserRole;
use App\Modules\Users\Application\ChangeUserStatus;
use App\Modules\Users\Application\Data\UserRecord;
use App\Modules\Users\Application\ResetUserAccess;
use App\Modules\Users\Application\UserProfiles;
use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Http\Requests\UserAccessRequest;
use Illuminate\Http\JsonResponse;

final readonly class UserAccessController
{
    public function __construct(
        private RequestAdmin $requests,
        private UserResource $resource,
        private UserProfiles $profiles,
    ) {}

    public function role(UserAccessRequest $request, string $user, ChangeUserRole $change): JsonResponse
    {
        $admin = $this->requests->admin($request);

        return $this->respond($change->handle($admin, $user, $request->role(), $request->code(), $this->requests->context($request)), $admin);
    }

    public function permissions(UserAccessRequest $request, string $user, ChangeUserPermissions $change): JsonResponse
    {
        $admin = $this->requests->admin($request);

        return $this->respond($change->handle($admin, $user, $request->permissions(), $request->code(), $this->requests->context($request)), $admin);
    }

    public function activate(UserAccessRequest $request, string $user, ChangeUserStatus $change): JsonResponse
    {
        $admin = $this->requests->admin($request);

        return $this->respond($change->handle($admin, $user, true, $this->requests->context($request)), $admin);
    }

    public function deactivate(UserAccessRequest $request, string $user, ChangeUserStatus $change): JsonResponse
    {
        $admin = $this->requests->admin($request);

        return $this->respond($change->handle($admin, $user, false, $this->requests->context($request)), $admin);
    }

    public function resetMfa(UserAccessRequest $request, string $user, ResetUserAccess $reset): JsonResponse
    {
        $admin = $this->requests->admin($request);

        return $this->respond($reset->resetMfa($admin, $user, $request->code(), $this->requests->context($request)), $admin);
    }

    public function resetAccess(UserAccessRequest $request, string $user, ResetUserAccess $reset): JsonResponse
    {
        $admin = $this->requests->admin($request);

        return $this->respond($reset->resetAccess($admin, $user, $request->code(), $this->requests->context($request)), $admin);
    }

    private function respond(UserRecord $user, Administrator $admin): JsonResponse
    {
        return new JsonResponse(['data' => $this->resource->detail($this->profiles->of($user), $admin)]);
    }
}
