<?php

namespace Database\Factories;

use App\Models\PrestationDecoration;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrestationDecorationFactory extends Factory
{
    protected $model = \App\Models\PrestationDecoration::class;
    
    public function definition(): array
    {
        return [
            'nom' => $this->faker->words(2, true),
            'description' => $this->faker->sentence(),
            'photo' => 'prestations/' . $this->faker->slug() . '.jpg',
            'prix' => $this->faker->randomElement([5000, 12000, 30000, 45000]),
        ];
    }
}