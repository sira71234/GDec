<?php

namespace Database\Seeders;

use App\Models\ContenuVitrine;
use Illuminate\Database\Seeder;

/**
 * Contenu initial de la vitrine : bannière et une section de présentation.
 * La galerie reste vide au départ ; le front retombe sur ses visuels statiques.
 */
class VitrineSeeder extends Seeder
{
    public function run(): void
    {
        $banniere = [
            'titre' => 'Location et décoration événementielle',
            'sous_titre' => 'Mobilier, tentes et décors pour vos plus belles célébrations.',
        ];

        foreach ($banniere as $cle => $valeur) {
            ContenuVitrine::updateOrCreate(
                ['section' => ContenuVitrine::SECTION_BANNIERE, 'cle' => $cle],
                ['type' => ContenuVitrine::TYPE_TEXTE, 'valeur' => $valeur],
            );
        }

        ContenuVitrine::updateOrCreate(
            ['section' => ContenuVitrine::SECTION_LIBRE, 'titre' => 'Qui sommes-nous ?'],
            [
                'type' => ContenuVitrine::TYPE_TEXTE,
                'valeur' => 'GDec met à votre disposition un catalogue complet de matériel de location '
                    .'et un service de décoration sur mesure pour tous vos événements.',
                'ordre' => 0,
            ],
        );
    }
}
