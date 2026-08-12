<?php

namespace Database\Factories;

use App\Models\ElementDecor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ElementDecorFactory extends Factory
{
    protected $model = \App\Models\ElementDecor::class;
    
    public function definition(): array
    {
        return [
            'nom' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
        ];
    }
}