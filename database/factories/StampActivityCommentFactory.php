<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StampActivityComment>
 */
class StampActivityCommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'stamp_activity_id' => StampActivityFactory::new(),
            'comment' => $this->faker->sentence(),
        ];
    }
}
