<?php

namespace Database\Factories;

use App\Models\Materiel;
use Illuminate\Database\Eloquent\Factories\Factory;

class MaterielFactory extends Factory
{

    protected $model = \App\Models\Materiel::class;
    
    public function definition(): array
    {
        return [
            'nom' => $this->faker->words(3, true),
            'description' => $this->faker->sentence(),
            'photo' => 'materiels/' . $this->faker->slug() . '.jpg',
            'prix_unitaire' => $this->faker->randomElement([500, 1500, 5000, 15000]),
            'quantite_stock' => $this->faker->numberBetween(5, 200),
        ];
    }
}