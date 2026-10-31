<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Commande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PieceIdentiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_ladmin_peut_consulter_la_piece_identite_dune_commande()
    {
        Storage::fake('local');
        Storage::disk('local')->put('pieces_identite/test.jpg', 'contenu-image');
        $commande = Commande::factory()->create([
            'piece_identite_photo' => 'pieces_identite/test.jpg',
        ]);
        Sanctum::actingAs(Admin::factory()->create());

        $response = $this->get("/api/admin/commandes/{$commande->id}/piece-identite");

        $response->assertOk();
    }

    public function test_un_visiteur_ne_peut_pas_consulter_la_piece_identite()
    {
        Storage::fake('local');
        Storage::disk('local')->put('pieces_identite/test.jpg', 'contenu-image');
        $commande = Commande::factory()->create([
            'piece_identite_photo' => 'pieces_identite/test.jpg',
        ]);

        $response = $this->getJson("/api/admin/commandes/{$commande->id}/piece-identite");

        $response->assertUnauthorized();
    }

    public function test_une_commande_sans_piece_identite_renvoie_404()
    {
        Storage::fake('local');
        $commande = Commande::factory()->create(['piece_identite_photo' => null]);
        Sanctum::actingAs(Admin::factory()->create());

        $response = $this->getJson("/api/admin/commandes/{$commande->id}/piece-identite");

        $response->assertNotFound();
    }
}
