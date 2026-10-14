<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Admin>
 */
class AdminFactory extends Factory
{
    /**
     * Le modèle associé à cette factory.
     *
     * @var class-string<Admin>
     */
    protected $model = Admin::class;

    /**
     * Valeurs par défaut d'un administrateur.
     * Les champs mot_de_passe et code_admin sont automatiquement hachés
     * par les casts du modèle Admin.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'identifiant' => 'admin_'.$this->faker->unique()->numberBetween(1, 99999),
            'mot_de_passe' => 'password',
            'code_admin' => '123456',
        ];
    }
}
