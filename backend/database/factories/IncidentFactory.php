<?php

declare(strict_types=1);

// Incidentes de prueba. Requiere los catálogos sembrados (prioridades y estados).

namespace Database\Factories;

use App\Modules\Incidents\Infrastructure\Models\Incident;
use App\Modules\Incidents\Infrastructure\Models\Priority;
use App\Modules\Incidents\Infrastructure\Models\Status;
use App\Modules\Users\Infrastructure\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Incident>
 */
class IncidentFactory extends Factory
{
    protected $model = Incident::class;

    public function definition(): array
    {
        return [
            // Año 00: no choca con los que entrega el contador durante una prueba
            'code' => fake()->unique()->numerify('TZ-00-######'),
            'title' => fake()->sentence(6),
            'description' => fake()->paragraph(),
            'priority_id' => fn (): int => (int) Priority::query()->inRandomOrder()->value('id'),
            'status_id' => fn (): int => (int) Status::query()->where('is_initial', true)->value('id'),
            'created_by' => User::factory(),
            'assigned_to' => null,
            'due_date' => fake()->dateTimeBetween('-10 days', '+20 days'),
        ];
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (): array => ['assigned_to' => $user->id]);
    }
}
