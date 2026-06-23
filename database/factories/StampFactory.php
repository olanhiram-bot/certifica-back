<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Stamp>
 */
class StampFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'is_active' => true,
            'vigency_start_date' => $this->faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
            'vigency_end_date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'percent' => $this->faker->numberBetween(1, 100),
            'provider_id' => UserFactory::new(),
            'course_call_id' => CourseCallFactory::new(),
            'modality_type_id' => ModalityTypeFactory::new(),
            'stamp_status_id' => StampStatusFactory::new(),
        ];
    }
}
