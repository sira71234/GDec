<?php

namespace Tests\Feature;

use App\Mail\CommandeConfirmationMail;
use App\Models\Commande;
use App\Models\ElementDecor;
use App\Models\Materiel;
use App\Models\PrestationDecoration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommandeStoreTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Mail::fake();
    }

    public function test_la_soumission_dune_commande_location_est_enregistree_avec_sa_caution()
    {
        $materiel = Materiel::factory()->create([
            'prix_unitaire' => 1000,
            'quantite_stock' => 10,
        ]);

        $payload = [
            'type_prestation' => 'location',
            'client' => [
                'nom' => 'Awa Diop',
                'telephone' => '0102030405',
                'email' => 'awa@test.com',
                'adresse' => 'Cotonou',
            ],
            'date_debut_location' => now()->addDays(5)->toDateString(),
            'date_fin_location' => now()->addDays(7)->toDateString(),
            'piece_identite_type' => 'CNI',
            'piece_identite_photo' => UploadedFile::fake()->image('cni.jpg'),
            'materiels' => [
                ['id' => $materiel->id, 'quantite' => 2],
            ],
        ];

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post('/api/commandes', $payload);

        $response->assertCreated();

        $this->assertDatabaseHas('clients', ['nom' => 'Awa Diop', 'telephone' => '0102030405']);
        $this->assertDatabaseHas('commandes', [
            'type_prestation' => 'location',
            'statut' => 'en_attente',
        ]);

        $commande = Commande::first();
        // totalLocation = 1000 × 2 × 3 jours = 6000 ; caution = 30 % = 1800.
        $this->assertEquals(1800, (float) $commande->montant_caution);
        $this->assertDatabaseHas('commande_materiel', [
            'commande_id' => $commande->id,
            'materiel_id' => $materiel->id,
            'quantite' => 2,
        ]);

        Mail::assertQueued(CommandeConfirmationMail::class);
        $this->assertNotEmpty(Storage::disk('public')->allFiles('devis'));
    }

    public function test_la_soumission_dune_commande_decoration_enregistre_prestations_et_elements()
    {
        $prestation = PrestationDecoration::factory()->create(['prix' => 5000]);
        $element = ElementDecor::factory()->create();

        $payload = [
            'type_prestation' => 'decoration',
            'client' => [
                'nom' => 'Kossi',
                'telephone' => '0700000000',
                'adresse' => 'Cotonou',
            ],
            'type_evenement' => 'Mariage',
            'date_evenement' => now()->addDays(10)->toDateString(),
            'duree_decoration' => 2,
            'theme' => 'Champêtre',
            'couleurs' => 'Vert et blanc',
            'nombre_personnes' => 120,
            'prestations_decoration' => [
                ['id' => $prestation->id, 'quantite' => 2],
            ],
            'elements_decor' => [$element->id],
        ];

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->post('/api/commandes', $payload);

        $response->assertCreated();

        $commande = Commande::first();
        $this->assertEquals('decoration', $commande->type_prestation);
        // Décoration seule : aucune caution.
        $this->assertEquals(0, (float) $commande->montant_caution);
        $this->assertDatabaseHas('commande_decoration', [
            'commande_id' => $commande->id,
            'prestation_decoration_id' => $prestation->id,
            'quantite' => 2,
        ]);
        $this->assertDatabaseHas('commande_elements_decor', [
            'commande_id' => $commande->id,
            'element_decor_id' => $element->id,
        ]);
    }

    public function test_une_commande_location_sans_piece_identite_est_rejetee()
    {
        $materiel = Materiel::factory()->create();

        $payload = [
            'type_prestation' => 'location',
            'client' => ['nom' => 'Test', 'telephone' => '0102'],
            'date_debut_location' => now()->addDay()->toDateString(),
            'date_fin_location' => now()->addDays(2)->toDateString(),
            'materiels' => [['id' => $materiel->id, 'quantite' => 1]],
        ];

        $response = $this->withHeaders(['Accept' => 'application/json'])
            ->postJson('/api/commandes', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['piece_identite_type', 'piece_identite_photo']);
    }

    public function test_le_type_de_prestation_est_obligatoire()
    {
        $response = $this->postJson('/api/commandes', [
            'client' => ['nom' => 'Test', 'telephone' => '0102'],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type_prestation']);
    }

    public function test_une_decoration_sans_date_ni_duree_est_rejetee()
    {
        $prestation = PrestationDecoration::factory()->create();

        $response = $this->postJson('/api/commandes', [
            'type_prestation' => 'decoration',
            'client' => ['nom' => 'Kossi', 'telephone' => '0700000000'],
            'type_evenement' => 'Mariage',
            'prestations_decoration' => [
                ['id' => $prestation->id, 'quantite' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['date_evenement', 'duree_decoration']);
    }

    public function test_le_telephone_identifie_le_client_et_regroupe_ses_commandes_avec_les_memes_coordonnees()
    {
        $materiel = Materiel::factory()->create(['prix_unitaire' => 1000]);

        $payload = [
            'type_prestation' => 'location',
            'client' => ['nom' => 'Même client', 'telephone' => '0199999999', 'adresse' => 'Cotonou'],
            'date_debut_location' => now()->addDays(5)->toDateString(),
            'date_fin_location' => now()->addDays(6)->toDateString(),
            'piece_identite_type' => 'CNI',
            'piece_identite_photo' => UploadedFile::fake()->image('cni.jpg'),
            'materiels' => [['id' => $materiel->id, 'quantite' => 1]],
        ];

        $this->withHeaders(['Accept' => 'application/json'])->post('/api/commandes', $payload)->assertCreated();
        $this->withHeaders(['Accept' => 'application/json'])->post('/api/commandes', $payload)->assertCreated();

        // Une seule fiche client est réutilisée pour les deux commandes.
        $this->assertDatabaseCount('clients', 1);
        $this->assertDatabaseHas('clients', ['telephone' => '0199999999', 'nom' => 'Même client']);
        $this->assertDatabaseCount('commandes', 2);
    }
}
