<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Materiel;
use App\Models\PrestationDecoration;
use App\Models\ElementDecor; // 1. Import du modèle manquant

class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Insertion sécurisée du matériel de location
        $materiels = [
            [
                'nom' => 'Chaise Napoléon blanche',
                'description' => 'Chaise élégante pour cérémonies et réceptions',
                'photo' => 'materiels/chaise-napoleon.jpg',
                'prix_unitaire' => 500,
                'quantite_stock' => 200,
            ],
            [
                'nom' => 'Table ronde 10 personnes',
                'description' => 'Table ronde en bois, idéale pour les repas',
                'photo' => 'materiels/table-ronde.jpg',
                'prix_unitaire' => 2500,
                'quantite_stock' => 30,
            ],
            [
                'nom' => 'Tente de réception 6x10m',
                'description' => 'Tente imperméable pour événements en extérieur',
                'photo' => 'materiels/tente.jpg',
                'prix_unitaire' => 15000,
                'quantite_stock' => 5,
            ],
            [
                'nom' => 'Sonorisation complète',
                'description' => 'Enceintes, micro, table de mixage',
                'photo' => 'sono.jpg',
                'prix_unitaire' => 20000,
                'quantite_stock' => 3,
            ],
        ];

        foreach ($materiels as $materiel) {
            // Se base sur le 'nom' pour mettre à jour ou créer sans dupliquer
            Materiel::updateOrCreate(['nom' => $materiel['nom']], $materiel);
        }

        // 2. Insertion sécurisée des prestations de décoration
        $prestations = [
            [
                'nom' => 'Arche florale',
                'description' => 'Arche décorée de fleurs naturelles ou artificielles',
                'photo' => 'decoration/arche.jpg',
                'prix' => 30000,
            ],
            [
                'nom' => 'Nappage de table',
                'description' => 'Nappes et sous-nappes assorties au thème',
                'photo' => 'decoration/nappage.jpg',
                'prix' => 5000,
            ],
            [
                'nom' => 'Composition florale centrale',
                'description' => 'Bouquet décoratif pour table d\'honneur',
                'photo' => 'decoration/composition-florale.jpg',
                'prix' => 12000,
            ],
        ];

        foreach ($prestations as $prestation) {
            PrestationDecoration::updateOrCreate(['nom' => $prestation['nom']], $prestation);
        }

        // 3. REPARATION : Ajout des éléments à décorer (Supprime ton warning Tinker)
        $elements = [
            ['nom' => 'Table d\'honneur'],
            ['nom' => 'Espace Photo / Photobooth'],
            ['nom' => 'Allée d\'honneur / Église'],
            ['nom' => 'Plafond de la salle'],
        ];

        foreach ($elements as $element) {
            ElementDecor::updateOrCreate(['nom' => $element['nom']], $element);
        }
    }
}
