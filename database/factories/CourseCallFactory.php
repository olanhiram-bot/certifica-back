<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseCall>
 */
class CourseCallFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // 'id' => $this->faker->unique()->numberBetween(2, 1000),
            'name' => $this->faker->sentence(3),
            'vigency_start_date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'vigency_end_date' => $this->faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
        ];
    }
}
