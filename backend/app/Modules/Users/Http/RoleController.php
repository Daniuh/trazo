<?php

declare(strict_types=1);

// Endpoints de roles: listar (también para los formularios de personas), ver, crear, editar y eliminar. Cada uno
// delega en su caso de uso. Documentado en docs/backend/users.md

namespace App\Modules\Users\Http;

use App\Modules\Users\Application\CreateRole;
use App\Modules\Users\Application\Data\RoleDetail;
use App\Modules\Users\Application\DeleteRole;
use App\Modules\Users\Application\ListRoles;
use App\Modules\Users\Application\RoleGate;
use App\Modules\Users\Application\UpdateRole;
use App\Modules\Users\Http\Requests\RoleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final readonly class RoleController
{
    public function __construct(
        private RequestAdmin $requests,
        private RoleResource $resource,
    ) {}

    public function index(Request $request, ListRoles $list): JsonResponse
    {
        $admin = $this->requests->admin($request);

        return new JsonResponse([
            'data' => array_map(fn (RoleDetail $role): array => $this->resource->toArray($role, $admin), $list->handle($admin)),
        ]);
    }

    public function show(Request $request, string $role, RoleGate $gate): JsonResponse
    {
        $admin = $this->requests->admin($request);

        return new JsonResponse(['data' => $this->resource->toArray($gate->load($admin, $role), $admin)]);
    }

    public function store(RoleRequest $request, CreateRole $create): JsonResponse
    {
        $admin = $this->requests->admin($request);
        $role = $create->handle($admin, $request->code(), $request->draft(), $request->mfaCode(), $this->requests->context($request));

        return new JsonResponse(['data' => $this->resource->toArray($role, $admin)], 201);
    }

    public function update(RoleRequest $request, string $role, UpdateRole $update): JsonResponse
    {
        $admin = $this->requests->admin($request);
        $saved = $update->handle($admin, $role, $request->draft(), $request->mfaCode(), $this->requests->context($request));

        return new JsonResponse(['data' => $this->resource->toArray($saved, $admin)]);
    }

    public function destroy(Request $request, string $role, DeleteRole $delete): Response
    {
        $delete->handle($this->requests->admin($request), $role, $this->requests->context($request));

        return response()->noContent();
    }
}
