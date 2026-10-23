<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Commande;
use App\Models\Devis;
use App\Models\Materiel;
use App\Models\PrestationDecoration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DevisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_ladmin_peut_generer_un_devis_pour_une_commande()
    {
        Sanctum::actingAs(Admin::factory()->create());

        $commande = Commande::factory()->lesDeux()->create();
        $materiel = Materiel::factory()->create(['prix_unitaire' => 2000]);
        $commande->materiels()->attach($materiel->id, ['quantite' => 3]);

        $response = $this->postJson("/api/admin/commandes/{$commande->id}/devis");

        $response->assertCreated();
        $response->assertJsonStructure(['message', 'devis' => ['id', 'fichier_pdf', 'montant_total'], 'url']);

        $devis = Devis::first();
        $this->assertEquals($commande->id, $devis->commande_id);
        Storage::disk('public')->assertExists($devis->fichier_pdf);
    }

    public function test_un_devis_est_toujours_rattache_a_sa_commande()
    {
        $commande = Commande::factory()->create();
        $devis = Devis::create([
            'commande_id' => $commande->id,
            'fichier_pdf' => 'devis/test.pdf',
            'montant_total' => 10000,
            'date_generation' => now(),
        ]);

        $this->assertEquals($commande->id, $devis->commande->id);
    }

    public function test_la_generation_de_devis_necessite_une_authentification()
    {
        $commande = Commande::factory()->create();

        $this->postJson("/api/admin/commandes/{$commande->id}/devis")
            ->assertStatus(401);
    }

    public function test_le_devis_utilise_le_montant_de_decoration_fixe_par_ladmin()
    {
        Sanctum::actingAs(Admin::factory()->create());

        $commande = Commande::factory()->decoration()->create(['montant_decoration' => null]);
        $prestation = PrestationDecoration::factory()->create(['prix' => 20000]);
        $commande->prestationsDecoration()->attach($prestation->id, ['quantite' => 1]);

        // L'admin fixe un forfait décoration supérieur au calcul automatique.
        $commande->update(['montant_decoration' => 75000]);

        $this->postJson("/api/admin/commandes/{$commande->id}/devis")->assertCreated();

        $this->assertEquals(75000, (float) Devis::first()->montant_total);
    }
}
