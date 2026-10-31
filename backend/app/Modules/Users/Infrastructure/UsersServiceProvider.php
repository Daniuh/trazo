<?php

declare(strict_types=1);

// Conecta el módulo de usuarios con Laravel: implementaciones de los puertos. Documentado en docs/backend/users.md

namespace App\Modules\Users\Infrastructure;

use App\Modules\Users\Application\Ports\AccountNotifier;
use App\Modules\Users\Application\Ports\AccountSecurity;
use App\Modules\Users\Application\Ports\AdminLog;
use App\Modules\Users\Application\Ports\EffectivePermissions;
use App\Modules\Users\Application\Ports\RoleCatalog;
use App\Modules\Users\Application\Ports\RoleRepository;
use App\Modules\Users\Application\Ports\UserListQuery;
use App\Modules\Users\Application\Ports\UserRepository;
use Illuminate\Support\ServiceProvider;

final class UsersServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    public array $bindings = [
        UserRepository::class => EloquentUserRepository::class,
        UserListQuery::class => EloquentUserList::class,
        RoleRepository::class => DatabaseRoleRepository::class,
        AccountSecurity::class => AuthAccountSecurity::class,
    ];

    public function register(): void
    {
        // Una sola lectura del catálogo, de los tipos de acción y de los permisos por petición
        $this->app->scoped(RoleCatalog::class, DatabaseRoleCatalog::class);
        $this->app->scoped(AdminLog::class, DatabaseAdminLog::class);
        $this->app->scoped(EffectivePermissions::class, fn ($app): EffectivePermissions => $app->make(UserPermissions::class));

        $this->app->bind(AccountNotifier::class, fn (): AccountNotifier => new MailAccountNotifier(
            (string) config('app.url'),
            (string) config('trazo.dashboard.timezone'),
        ));
    }
}
