<?php

declare(strict_types=1);

// Juegos de cuentas por rol desde el entorno (ADR-0087): una cuenta por rol, sin verificación en dos pasos, sin pisar
// cambios posteriores y sin crear nada si el patrón o la contraseña no sirven. Documentado en docs/operacion/cuentas.md

use App\Modules\Users\Infrastructure\Models\User;
use Database\Seeders\AccountSetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

// Contraseña inventada para la prueba: no existe en ningún entorno
const SET_PASSWORD = 'Lago.Verde.Norte.2026'; // gitleaks:allow

beforeEach(function (): void {
    // Have I Been Pwned responde sin coincidencias: la contraseña de la prueba no está filtrada
    Http::fake(['api.pwnedpasswords.com/range/*' => Http::response('')]);
    config(['trazo.account_sets' => [
        'evaluation' => ['label' => 'Evaluación', 'email_pattern' => 'Eval-{rol}@Empresa.com', 'password' => SET_PASSWORD],
        'owner' => ['label' => 'Daniel Naranjo', 'email_pattern' => null, 'password' => null],
    ]]);
});

function setAccounts(): array
{
    return User::query()->where('email', 'like', 'eval-%@empresa.com')->with('roles:id,code')->orderBy('email')->get()
        ->mapWithKeys(fn (User $user): array => [$user->email => [$user->name, $user->roles->pluck('code')->all()]])->all();
}

it('crea una cuenta por rol con el correo del patrón, sin verificación en dos pasos', function (): void {
    $this->seed(AccountSetSeeder::class);

    expect(setAccounts())->toBe([
        'eval-admin@empresa.com' => ['Evaluación Administrador', ['admin']],
        'eval-agente@empresa.com' => ['Evaluación Agente', ['agent']],
        'eval-solicitante@empresa.com' => ['Evaluación Solicitante', ['requester']],
        'eval-superadmin@empresa.com' => ['Evaluación Super admin', ['super_admin']],
        'eval-supervisor@empresa.com' => ['Evaluación Supervisor', ['supervisor']],
    ]);

    $user = User::query()->where('email', 'eval-supervisor@empresa.com')->firstOrFail();
    expect(Hash::check(SET_PASSWORD, $user->getAuthPassword()))->toBeTrue()
        ->and(DB::table('user_mfa_factors')->where('user_id', $user->id)->exists())->toBeFalse();

    // El primer ingreso sugiere configurar la verificación: muestra el QR
    $this->postJson('/api/v1/auth/login', ['email' => 'eval-supervisor@empresa.com', 'password' => SET_PASSWORD])
        ->assertOk()->assertJsonPath('data.status', 'setup_suggested');
});

it('solo crea las que faltan y no pisa lo que cambió después', function (): void {
    $this->seed(AccountSetSeeder::class);
    User::query()->where('email', 'eval-agente@empresa.com')->update(['password' => Hash::make('Otra.Clave.Segura.2026')]);
    User::query()->where('email', 'eval-admin@empresa.com')->delete();

    $this->seed(AccountSetSeeder::class);

    $agent = User::query()->where('email', 'eval-agente@empresa.com')->firstOrFail();
    expect(setAccounts())->toHaveCount(5)
        ->and(Hash::check('Otra.Clave.Segura.2026', $agent->getAuthPassword()))->toBeTrue()
        ->and(DB::table('user_role')->where('user_id', $agent->id)->count())->toBe(1);
});

it('no crea nada si el patrón no lleva {rol} o la contraseña no cumple la política', function (array $set): void {
    config(['trazo.account_sets.evaluation' => [...config('trazo.account_sets.evaluation'), ...$set]]);

    $this->seed(AccountSetSeeder::class);

    expect(setAccounts())->toBe([]);
})->with([
    'sin {rol}' => [['email_pattern' => 'eval@empresa.com']],
    'muy corta' => [['password' => 'Corta.1']],
    'predecible' => [['password' => 'aaaaaaaaaaaaaaaa']],
]);
