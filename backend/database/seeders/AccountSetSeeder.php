<?php

declare(strict_types=1);

// Juegos de cuentas, una por rol, que se crean en cualquier entorno (también en producción) a partir de variables que
// solo existen en el servidor: las de quienes evalúan y las propias. Nacen sin verificación en dos pasos, así que el
// primer ingreso pide escanear el QR. Solo crea las que falten: un cambio hecho después no se pisa (ADR-0087).
// Documentado en docs/operacion/cuentas.md

namespace Database\Seeders;

use App\Modules\Auth\Http\Rules\NewPassword;
use App\Modules\Shared\Domain\EmailAddress;
use App\Modules\Users\Infrastructure\Models\Role;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Validator;

class AccountSetSeeder extends Seeder
{
    // Lo que reemplaza a {rol} en el correo, por código de rol
    public const ROLE_SLUGS = [
        'super_admin' => 'superadmin',
        'admin' => 'admin',
        'supervisor' => 'supervisor',
        'agent' => 'agente',
        'requester' => 'solicitante',
    ];

    public function run(): void
    {
        /** @var array<string, array{label: string, email_pattern: ?string, password: ?string}> $sets */
        $sets = (array) config('trazo.account_sets');

        foreach ($sets as $key => $set) {
            $pattern = trim((string) $set['email_pattern']);
            $password = (string) $set['password'];
            if ($pattern === '' || $password === '') {
                continue;
            }

            $problem = $this->problem($pattern, $password);
            if ($problem !== null) {
                // Un juego mal configurado no detiene el arranque del sitio: se avisa y se omite
                $this->command?->error("Cuentas \"{$key}\" no creadas: {$problem}");

                continue;
            }

            $created = $this->create($pattern, $password, (string) $set['label']);
            $this->command?->info("Cuentas \"{$key}\": {$created} nuevas");
        }
    }

    private function problem(string $pattern, string $password): ?string
    {
        if (! str_contains($pattern, '{rol}')) {
            return 'el patrón del correo debe llevar {rol}.';
        }

        $check = Validator::make(
            ['password' => $password, 'password_confirmation' => $password],
            ['password' => NewPassword::rules()],
        );

        return $check->fails() ? 'la contraseña no cumple la política (12 o más caracteres, no predecible y no filtrada).' : null;
    }

    private function create(string $pattern, string $password, string $label): int
    {
        $roles = Role::query()->whereIn('code', array_keys(self::ROLE_SLUGS))->get()->keyBy('code');
        $created = 0;

        foreach (self::ROLE_SLUGS as $code => $slug) {
            $role = $roles->get($code);
            if ($role === null) {
                continue;
            }

            $user = User::query()->firstOrCreate(
                ['email' => EmailAddress::normalize(str_replace('{rol}', $slug, $pattern))],
                ['given_names' => $label, 'family_names' => $role->name, 'password' => $password, 'is_active' => true],
            );
            if ($user->wasRecentlyCreated) {
                $user->roles()->attach($role->id);
                $created++;
            }
        }

        return $created;
    }
}
