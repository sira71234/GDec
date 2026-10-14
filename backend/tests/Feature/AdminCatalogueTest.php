<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ElementDecor;
use App\Models\Materiel;
use App\Models\PrestationDecoration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminCatalogueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Sanctum::actingAs(Admin::factory()->create());
    }

    // ==========================================================
    // MATÉRIELS
    // ==========================================================

    public function test_ladmin_peut_lister_les_materiels()
    {
        Materiel::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/materiels');

        $response->assertOk()->assertJsonCount(3);
    }

    public function test_ladmin_peut_ajouter_un_materiel_avec_photo()
    {
        $response = $this->post('/api/admin/materiels', [
            'nom' => 'Tente 4x4',
            'description' => 'Tente pliable',
            'prix_unitaire' => 25000,
            'quantite_stock' => 8,
            'photo' => UploadedFile::fake()->image('tente.jpg'),
        ]);

        $response->assertCreated();

        $materiel = Materiel::first();
        $this->assertSame('Tente 4x4', $materiel->nom);
        Storage::disk('public')->assertExists($materiel->photo);
    }

    public function test_la_photo_est_obligatoire_pour_creer_un_materiel()
    {
        $response = $this->postJson('/api/admin/materiels', [
            'nom' => 'Sans photo',
            'prix_unitaire' => 1000,
            'quantite_stock' => 1,
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['photo']);
    }

    public function test_ladmin_peut_modifier_un_materiel_et_remplacer_sa_photo()
    {
        $materiel = Materiel::factory()->create(['photo' => 'materiels/ancienne.jpg']);
        Storage::disk('public')->put('materiels/ancienne.jpg', 'contenu');

        $response = $this->post("/api/admin/materiels/{$materiel->id}", [
            'nom' => 'Tente rénovée',
            'prix_unitaire' => 30000,
            'quantite_stock' => 5,
            'photo' => UploadedFile::fake()->image('nouvelle.jpg'),
        ]);

        $response->assertOk();

        $materiel->refresh();
        $this->assertSame('Tente rénovée', $materiel->nom);
        Storage::disk('public')->assertMissing('materiels/ancienne.jpg');
        Storage::disk('public')->assertExists($materiel->photo);
    }

    public function test_ladmin_peut_retirer_un_materiel()
    {
        $materiel = Materiel::factory()->create();

        $response = $this->deleteJson("/api/admin/materiels/{$materiel->id}");

        $response->assertOk();
        $this->assertSoftDeleted('materiels', ['id' => $materiel->id]);
    }

    // ==========================================================
    // PRESTATIONS DE DÉCORATION
    // ==========================================================

    public function test_ladmin_peut_ajouter_et_modifier_une_prestation()
    {
        $creation = $this->post('/api/admin/prestations-decoration', [
            'nom' => 'Arche florale',
            'prix' => 45000,
            'photo' => UploadedFile::fake()->image('arche.jpg'),
        ]);

        $creation->assertCreated();
        $prestation = PrestationDecoration::first();

        $miseAJour = $this->postJson("/api/admin/prestations-decoration/{$prestation->id}", [
            'nom' => 'Arche florale premium',
            'prix' => 60000,
        ]);

        $miseAJour->assertOk();
        $this->assertSame('Arche florale premium', $prestation->fresh()->nom);
    }

    public function test_ladmin_peut_retirer_une_prestation()
    {
        $prestation = PrestationDecoration::factory()->create();

        $this->deleteJson("/api/admin/prestations-decoration/{$prestation->id}")->assertOk();

        $this->assertSoftDeleted('prestations_decoration', ['id' => $prestation->id]);
    }

    // ==========================================================
    // ÉLÉMENTS DE DÉCORATION
    // ==========================================================

    public function test_ladmin_gere_les_elements_de_decoration()
    {
        $creation = $this->postJson('/api/admin/elements-decor', [
            'nom' => 'Nappe dorée',
            'description' => 'Nappe satinée',
        ]);
        $creation->assertCreated();
        $element = ElementDecor::first();

        $this->getJson('/api/admin/elements-decor')->assertOk()->assertJsonCount(1);

        $this->postJson("/api/admin/elements-decor/{$element->id}", [
            'nom' => 'Nappe argentée',
        ])->assertOk();
        $this->assertSame('Nappe argentée', $element->fresh()->nom);

        $this->deleteJson("/api/admin/elements-decor/{$element->id}")->assertOk();
        $this->assertSoftDeleted('elements_decor', ['id' => $element->id]);
    }

    // ==========================================================
    // PROTECTION
    // ==========================================================

    public function test_un_visiteur_ne_peut_pas_acceder_au_catalogue_admin()
    {
        $this->app['auth']->forgetGuards();

        $this->getJson('/api/admin/materiels')->assertUnauthorized();
    }
}
