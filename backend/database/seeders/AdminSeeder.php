<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Crée le compte administrateur par défaut.
     * Les valeurs sont volontairement simples pour le développement :
     * à changer impérativement avant toute mise en production.
     */
    public function run(): void
    {
        Admin::updateOrCreate(
            ['identifiant' => 'admin'],
            [
                'mot_de_passe' => 'admin123',
                'code_admin' => '000000',
            ],
        );
    }
}
