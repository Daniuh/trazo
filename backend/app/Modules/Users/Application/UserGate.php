<?php

declare(strict_types=1);

// Carga una persona solo si quien administra puede ver usuarios y hacer la acción pedida; si no, lanza el error que
// corresponde. Lo usan todos los casos de uso, así nadie se salta las reglas. Documentado en docs/backend/users.md

namespace App\Modules\Users\Application;

use App\Modules\Shared\Domain\Exceptions\ForbiddenException;
use App\Modules\Users\Application\Data\UserRecord;
use App\Modules\Users\Application\Ports\UserRepository;
use App\Modules\Users\Domain\Administrator;
use App\Modules\Users\Domain\Exceptions\UserExceptions;
use App\Modules\Users\Domain\UserAccess;
use App\Modules\Users\Domain\UserAction;

final readonly class UserGate
{
    public function __construct(
        private UserRepository $users,
        private UserAccess $access,
    ) {}

    // Toda la administración empieza por poder ver usuarios
    public function ensureCanView(Administrator $admin): void
    {
        if (! $admin->can('users.view')) {
            throw new ForbiddenException;
        }
    }

    public function load(Administrator $admin, string $ulid, ?UserAction $action = null): UserRecord
    {
        $this->ensureCanView($admin);
        $user = $this->users->find($ulid) ?? throw UserExceptions::notFound();

        if ($action !== null) {
            $denial = $this->access->denial($action, $admin, $user->managed());
            if ($denial !== null) {
                throw UserExceptions::denied($denial);
            }
        }

        return $user;
    }

    // Recargar después de un cambio, para responder con lo que quedó guardado
    public function reload(UserRecord $user): UserRecord
    {
        return $this->users->findById($user->id) ?? throw UserExceptions::notFound();
    }
}
