<?php

declare(strict_types=1);

// Endpoints de personas: listar, crear, ver y editar. Cada uno delega en su caso de uso.
// Documentado en docs/backend/users.md

namespace App\Modules\Users\Http;

use App\Modules\Users\Application\CreateUser;
use App\Modules\Users\Application\Data\UserRecord;
use App\Modules\Users\Application\ListUsers;
use App\Modules\Users\Application\UpdateUser;
use App\Modules\Users\Application\UserGate;
use App\Modules\Users\Application\UserProfiles;
use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Http\Requests\ListUsersRequest;
use App\Modules\Users\Http\Requests\UserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class UserController
{
    public function __construct(
        private RequestAdmin $requests,
        private UserResource $resource,
        private UserProfiles $profiles,
    ) {}

    public function index(ListUsersRequest $request, ListUsers $list): JsonResponse
    {
        $page = $list->handle($this->requests->admin($request), $request->criteria());

        return new JsonResponse([
            'data' => array_map(fn (UserRecord $user): array => $this->resource->summary($user), $page->items),
            'meta' => ['page' => $page->page, 'per_page' => $page->perPage, 'total' => $page->total, 'last_page' => $page->lastPage()],
        ]);
    }

    public function store(UserRequest $request, CreateUser $create): JsonResponse
    {
        $admin = $this->requests->admin($request);
        $user = $create->handle($admin, $request->newUser(), $request->mfaCode(), $this->requests->context($request));

        return new JsonResponse(['data' => $this->detail($user, $admin)], 201);
    }

    public function show(Request $request, string $user, UserGate $gate): JsonResponse
    {
        $admin = $this->requests->admin($request);

        return new JsonResponse(['data' => $this->detail($gate->load($admin, $user), $admin)]);
    }

    public function update(UserRequest $request, string $user, UpdateUser $update): JsonResponse
    {
        $admin = $this->requests->admin($request);
        $record = $update->handle($admin, $user, $request->personName(), $request->email(), $this->requests->context($request));

        return new JsonResponse(['data' => $this->detail($record, $admin)]);
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(UserRecord $user, Administrator $admin): array
    {
        return $this->resource->detail($this->profiles->of($user), $admin);
    }
}
