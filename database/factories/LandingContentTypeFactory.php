<?php

namespace Database\Factories;

use App\Models\LandingContentType;
use Illuminate\Database\Eloquent\Factories\Factory;

class LandingContentTypeFactory extends Factory
{
    protected $model = LandingContentType::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word(),
        ];
    }
}
