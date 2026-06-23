<?php

namespace Database\Factories;

use App\Models\LandingContent;
use Illuminate\Database\Eloquent\Factories\Factory;

class LandingContentFactory extends Factory
{
    protected $model = LandingContent::class;

    public function definition()
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'file_url' => null,
            'position' => $this->faker->numberBetween(1, 10),
            'landing_content_type_id' => LandingContentTypeFactory::new(),
        ];
    }
}
