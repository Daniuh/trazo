<?php

declare(strict_types=1);

// Usuarios de demostración, uno o más por rol. Nunca se crean en producción.
// Idempotente: si el correo ya existe no lo toca (tampoco su contraseña).
// Documentado en docs/base-de-datos/seeders.md

namespace Database\Seeders;

use App\Modules\Users\Infrastructure\Models\Role;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    /** Correo => [nombres, apellidos, rol] */
    private const USERS = [
        'superadmin@trazo.test' => ['Sofía', 'Ramírez', 'super_admin'],
        'admin@trazo.test' => ['Natalia', 'Vargas', 'admin'],
        'supervisor@trazo.test' => ['Carlos', 'Mejía', 'supervisor'],
        'agente1@trazo.test' => ['Laura', 'Gómez', 'agent'],
        'agente2@trazo.test' => ['Andrés', 'Castro', 'agent'],
        'agente3@trazo.test' => ['Valentina', 'Ríos', 'agent'],
        'solicitante1@trazo.test' => ['Juan', 'Pérez', 'requester'],
        'solicitante2@trazo.test' => ['María', 'López', 'requester'],
        'solicitante3@trazo.test' => ['Diego', 'Herrera', 'requester'],
    ];

    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        $roles = Role::query()->pluck('id', 'code');
        $password = (string) config('trazo.demo_user_password');

        foreach (self::USERS as $email => [$given, $family, $role]) {
            $user = User::query()->firstOrCreate(
                ['email' => $email],
                ['given_names' => $given, 'family_names' => $family, 'password' => $password, 'is_active' => true],
            );

            $user->roles()->syncWithoutDetaching([$roles[$role]]);
        }
    }
}
