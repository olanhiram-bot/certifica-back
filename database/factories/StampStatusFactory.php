<?php

namespace Database\Factories;

use App\Models\StampStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StampStatus>
 */
class StampStatusFactory extends Factory
{
    protected $model = StampStatus::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->randomElement(['Nuevo', 'Por Revisar', 'En Revisión', 'Aprobado', 'Vencido']),
        ];
    }
}
