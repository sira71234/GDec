<?php

namespace Database\Factories;

use App\Models\ContenuVitrine;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContenuVitrineFactory extends Factory
{
    protected $model = ContenuVitrine::class;

    public function definition(): array
    {
        return [
            'section' => ContenuVitrine::SECTION_LIBRE,
            'cle' => null,
            'type' => ContenuVitrine::TYPE_TEXTE,
            'titre' => $this->faker->words(3, true),
            'valeur' => $this->faker->paragraph(),
            'ordre' => 0,
        ];
    }

    public function banniere(string $cle = 'titre'): static
    {
        return $this->state(fn (): array => [
            'section' => ContenuVitrine::SECTION_BANNIERE,
            'cle' => $cle,
            'type' => ContenuVitrine::TYPE_TEXTE,
        ]);
    }

    public function galerie(): static
    {
        return $this->state(fn (): array => [
            'section' => ContenuVitrine::SECTION_GALERIE,
            'type' => ContenuVitrine::TYPE_IMAGE,
            'titre' => $this->faker->word(),
            'valeur' => 'vitrine/'.$this->faker->slug().'.jpg',
        ]);
    }
}
