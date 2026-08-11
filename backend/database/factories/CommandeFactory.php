<?php

namespace Database\Factories;

use App\Models\Commande;
use App\Models\Client;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Commande>
 */
class CommandeFactory extends Factory
{
    /**
     * Le nom du modèle correspondant à la factory.
     * Crucial pour que Laravel associe correctement cette usine au bon modèle.
     *
     * @var string
     */
    protected $model = Commande::class;

    /**
     * Définir l'état par défaut du modèle.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Crée automatiquement un nouveau client si aucun n'est précisé
            'client_id' => Client::factory(),
            'type_prestation' => $this->faker->randomElement(['location', 'decoration', 'les_deux']),
            'statut' => 'en_attente',
            'montant_caution' => $this->faker->randomElement([0, 25000, 50000]),
            'date_debut_location' => $this->faker->dateTimeBetween('+1 week', '+2 weeks')->format('Y-m-d'),
            'date_fin_location' => $this->faker->dateTimeBetween('+2 weeks', '+3 weeks')->format('Y-m-d'),
            'type_evenement' => $this->faker->randomElement(['Mariage', 'Anniversaire', 'Baptême', 'Séminaire']),
            'theme' => $this->faker->sentence(3),
            'couleurs' => $this->faker->randomElement(['Blanc et or', 'Rouge et blanc', 'Bleu et argent']),
            'nombre_personnes' => $this->faker->numberBetween(30, 300),
            'complement' => null,
        ];
    }

    /**
     * État "location" : uniquement les champs liés à la location de matériel.
     * Usage : Commande::factory()->location()->create();
     */
    public function location(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_prestation' => 'location',
            'type_evenement' => null,
            'theme' => null,
            'couleurs' => null,
        ]);
    }

    /**
     * État "decoration" : uniquement les champs liés à la décoration.
     * Usage : Commande::factory()->decoration()->create();
     */
    public function decoration(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_prestation' => 'decoration',
            'date_debut_location' => null,
            'date_fin_location' => null,
        ]);
    }

    /**
     * État "les_deux" : combine location + décoration.
     * Usage : Commande::factory()->lesDeux()->create();
     */
    public function lesDeux(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_prestation' => 'les_deux',
        ]);
    }
}
