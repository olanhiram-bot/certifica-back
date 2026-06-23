<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StampActivity>
 */
class StampActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stamp_id' => StampFactory::new(),
            'activity_id' => ActivityFactory::new(),
            'stamp_activity_status_id' => StampActivityStatusFactory::new(),
        ];
    }
}
