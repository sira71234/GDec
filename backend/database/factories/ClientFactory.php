<?php

namespace Database\Factories;

use App\Models\Client;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nom' => $this->faker->name(),
            'telephone' => $this->faker->numerify('01#######'),
            'email' => $this->faker->safeEmail(),
            'adresse' => $this->faker->address(),
        ];
    }
}