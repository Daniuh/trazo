<?php

declare(strict_types=1);

// Usuarios de prueba. La contraseña se calcula una sola vez para no repetir el hash en cada registro.

namespace Database\Factories;

use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    private static ?string $password = null;

    public function definition(): array
    {
        return [
            'given_names' => fake()->firstName(),
            'family_names' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => self::$password ??= Hash::make('password-de-prueba-123'),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
