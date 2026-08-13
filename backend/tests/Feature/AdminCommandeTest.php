<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Commande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommandeTest extends TestCase
{
    // Rejoue les migrations pour garantir une base SQLite propre en mémoire RAM à chaque test
    use RefreshDatabase;

    // ==========================================================
    // TESTS : index()
    // ==========================================================

    public function test_ladmin_peut_lister_toutes_les_commandes()
    {
        Commande::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/commandes');

        $response->assertOk();
        $response->assertJsonCount(3, 'data');
    }

    public function test_ladmin_peut_filtrer_les_commandes_par_statut()
    {
        Commande::factory()->create(['statut' => 'en_attente']);
        Commande::factory()->create(['statut' => 'valide']);
        Commande::factory()->create(['statut' => 'valide']);

        $response = $this->getJson('/api/admin/commandes?statut=valide');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
    }

    public function test_un_statut_invalide_dans_le_filtre_renvoie_une_erreur_422()
    {
        $response = $this->getJson('/api/admin/commandes?statut=nimportequoi');

        $response->assertStatus(422);
    }

    public function test_les_commandes_en_corbeille_napparaissent_pas_dans_index()
    {
        $commande = Commande::factory()->create();
        $commande->delete();

        $response = $this->getJson('/api/admin/commandes');

        $response->assertOk();
        $response->assertJsonCount(0, 'data');
    }

    // ==========================================================
    // TESTS : show()
    // ==========================================================

    public function test_ladmin_peut_voir_le_detail_dune_commande_avec_ses_relations()
    {
        $commande = Commande::factory()->create();

        $response = $this->getJson("/api/admin/commandes/{$commande->id}");

        $response->assertOk();
        
        // CORRECTION : Correspondance exacte avec les clés chargées par loadMissing() dans ton Controller
        $response->assertJsonStructure([
            'id',
            'client',
            'materiels',
            'prestations_decoration',
            'elements_decor',
            'devis',
        ]);
    }

    public function test_acceder_a_une_commande_inexistante_renvoie_404()
    {
        $response = $this->getJson('/api/admin/commandes/99999');

        $response->assertStatus(404);
    }

    // ==========================================================
    // TESTS : updateStatut()
    // ==========================================================

    public function test_ladmin_peut_valider_une_commande()
    {
        $commande = Commande::factory()->create(['statut' => 'en_attente']);

        $response = $this->patchJson("/api/admin/commandes/{$commande->id}/statut", [
            'statut' => 'valide',
        ]);

        $response->assertOk();
        $this->assertEquals('valide', $commande->fresh()->statut);
    }

    public function test_ladmin_peut_refuser_une_commande()
    {
        $commande = Commande::factory()->create(['statut' => 'en_attente']);

        $response = $this->patchJson("/api/admin/commandes/{$commande->id}/statut", [
            'statut' => 'refuse',
        ]);

        $response->assertOk();
        $this->assertEquals('refuse', $commande->fresh()->statut);
    }

    public function test_un_statut_invalide_est_rejete_a_la_mise_a_jour()
    {
        $commande = Commande::factory()->create();

        $response = $this->patchJson("/api/admin/commandes/{$commande->id}/statut", [
            'statut' => 'approuve',
        ]);

        $response->assertStatus(422);
    }

    // ==========================================================
    // TESTS : corbeille() / restaurer() / supprimerDefinitivement()
    // ==========================================================

    public function test_la_corbeille_liste_uniquement_les_commandes_supprimees()
    {
        Commande::factory()->create(); // Commande active
        $supprimee = Commande::factory()->create();
        $supprimee->delete(); // Soft delete

        $response = $this->getJson('/api/admin/commandes/corbeille');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
    }

    public function test_ladmin_peut_restaurer_une_commande_de_la_corbeille()
    {
        $commande = Commande::factory()->create();
        $commande->delete();

        $response = $this->patchJson("/api/admin/commandes/{$commande->id}/restaurer");

        $response->assertOk();
        $this->assertNull($commande->fresh()->deleted_at);
    }

    public function test_ladmin_peut_supprimer_definitivement_une_commande_sans_dependances()
    {
        $commande = Commande::factory()->create();
        $commande->delete();
        $commandeId = $commande->id;

        $response = $this->deleteJson("/api/admin/commandes/{$commandeId}/definitif");

        $response->assertOk();
        $this->assertDatabaseMissing('commandes', ['id' => $commandeId]);
    }
}
