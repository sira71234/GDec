<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Commande;
use App\Models\PrestationDecoration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class HistoriqueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Mail::fake();

        Sanctum::actingAs(Admin::factory()->create());
    }

    public function test_la_creation_dune_commande_est_journalisee()
    {
        $prestation = PrestationDecoration::factory()->create(['prix' => 5000]);

        $this->withHeaders(['Accept' => 'application/json'])->post('/api/commandes', [
            'type_prestation' => 'decoration',
            'client' => [
                'nom' => 'Kossi',
                'telephone' => '0700000000',
                'adresse' => 'Cotonou',
            ],
            'type_evenement' => 'Mariage',
            'date_evenement' => now()->addDays(10)->toDateString(),
            'duree_decoration' => 2,
            'prestations_decoration' => [
                ['id' => $prestation->id, 'quantite' => 1],
            ],
        ])->assertCreated();

        $commande = Commande::firstOrFail();

        $this->assertDatabaseHas('action_historiques', [
            'commande_id' => $commande->id,
            'action' => 'creation',
        ]);
    }

    public function test_une_modification_admin_est_journalisee_et_visible_dans_le_detail()
    {
        $commande = Commande::factory()->decoration()->create([
            'montant_decoration' => null,
        ]);

        $this->patchJson("/api/admin/commandes/{$commande->id}", [
            'montant_decoration' => 75000,
        ])->assertOk();

        $this->assertDatabaseHas('action_historiques', [
            'commande_id' => $commande->id,
            'action' => 'modification',
        ]);

        $response = $this->getJson("/api/admin/commandes/{$commande->id}");

        $response->assertOk();
        $response->assertJsonPath('historiques.0.action', 'modification');
        $response->assertJsonPath('historiques.0.commande_id', $commande->id);
    }

    public function test_un_changement_de_statut_est_journalise()
    {
        $commande = Commande::factory()->create(['statut' => 'en_attente']);

        $this->patchJson("/api/admin/commandes/{$commande->id}/statut", [
            'statut' => 'valide',
        ])->assertOk();

        $this->assertDatabaseHas('action_historiques', [
            'commande_id' => $commande->id,
            'action' => 'changement_statut',
        ]);
    }

    public function test_une_commande_passee_depuis_plus_de_deux_jours_quitte_les_demandes_actives()
    {
        $ancienne = Commande::factory()->location()->create([
            'statut' => 'valide',
            'date_debut_location' => now()->subDays(6)->toDateString(),
            'date_fin_location' => now()->subDays(3)->toDateString(),
        ]);

        $recente = Commande::factory()->location()->create([
            'statut' => 'valide',
            'date_debut_location' => now()->subDay()->toDateString(),
            'date_fin_location' => now()->toDateString(),
        ]);

        $this->getJson('/api/admin/commandes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $recente->id);

        $this->getJson('/api/admin/commandes/historique')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $ancienne->id);
    }

    public function test_une_decoration_passee_est_archivee()
    {
        $commande = Commande::factory()->decoration()->create([
            'statut' => 'valide',
            'date_evenement' => now()->subDays(4)->toDateString(),
            'duree_decoration' => 1,
        ]);

        $this->getJson('/api/admin/commandes')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/admin/commandes/historique')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $commande->id);
    }
}
