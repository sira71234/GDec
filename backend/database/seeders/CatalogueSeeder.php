<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CatalogueSeeder extends Seeder
{
    public function run(): void
    {
        // insertion donnee matériel de location
        DB::table('materiels')->insert([
            [
                'nom' => 'Chaise Napoléon blanche',
                'description' => 'Chaise élégante pour cérémonies et réceptions',
                'photo' => 'materiels/chaise-napoleon.jpg',
                'prix_unitaire' => 500,
                'quantite_stock' => 200,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Table ronde 10 personnes',
                'description' => 'Table ronde en bois, idéale pour les repas',
                'photo' => 'materiels/table-ronde.jpg',
                'prix_unitaire' => 2500,
                'quantite_stock' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Tente de réception 6x10m',
                'description' => 'Tente imperméable pour événements en extérieur',
                'photo' => 'materiels/tente.jpg',
                'prix_unitaire' => 15000,
                'quantite_stock' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Sonorisation complète',
                'description' => 'Enceintes, micro, table de mixage',
                'photo' => 'materiels/sono.jpg',
                'prix_unitaire' => 20000,
                'quantite_stock' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // insertion donnee prestations de décoration
        DB::table('prestations_decoration')->insert([
            [
                'nom' => 'Arche florale',
                'description' => 'Arche décorée de fleurs naturelles ou artificielles',
                'photo' => 'decoration/arche.jpg',
                'prix' => 30000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Nappage de table',
                'description' => 'Nappes et sous-nappes assorties au thème',
                'photo' => 'decoration/nappage.jpg',
                'prix' => 5000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nom' => 'Composition florale centrale',
                'description' => 'Bouquet décoratif pour table d\'honneur',
                'photo' => 'decoration/composition-florale.jpg',
                'prix' => 12000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}