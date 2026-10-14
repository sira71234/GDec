<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\ContenuVitrine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VitrineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    // ==========================================================
    // LECTURE PUBLIQUE
    // ==========================================================

    public function test_la_vitrine_publique_renvoie_des_valeurs_vides_par_defaut()
    {
        $response = $this->getJson('/api/vitrine');

        $response->assertOk()
            ->assertJsonPath('banniere.titre', null)
            ->assertJsonPath('banniere.sous_titre', null)
            ->assertJsonPath('galerie', [])
            ->assertJsonPath('sections', []);
    }

    public function test_la_vitrine_publique_reflete_le_contenu_enregistre()
    {
        ContenuVitrine::factory()->banniere('titre')->create(['valeur' => 'Bienvenue']);
        ContenuVitrine::factory()->galerie()->create(['titre' => 'Mariage', 'ordre' => 1]);
        ContenuVitrine::factory()->create(['titre' => 'Nos services', 'valeur' => 'Texte libre']);

        $response = $this->getJson('/api/vitrine');

        $response->assertOk()
            ->assertJsonPath('banniere.titre', 'Bienvenue')
            ->assertJsonCount(1, 'galerie')
            ->assertJsonPath('galerie.0.titre', 'Mariage')
            ->assertJsonCount(1, 'sections')
            ->assertJsonPath('sections.0.titre', 'Nos services');
    }

    // ==========================================================
    // ADMINISTRATION
    // ==========================================================

    public function test_ladmin_met_a_jour_la_banniere_avec_image()
    {
        Sanctum::actingAs(Admin::factory()->create());

        $response = $this->post('/api/admin/vitrine/banniere', [
            'titre' => 'Nouveau titre',
            'sous_titre' => 'Nouveau sous-titre',
            'image' => UploadedFile::fake()->image('banniere.jpg'),
        ]);

        $response->assertOk()
            ->assertJsonPath('vitrine.banniere.titre', 'Nouveau titre');

        $colonneImage = ContenuVitrine::where('cle', 'image')->value('valeur');
        $this->assertNotNull($colonneImage);
        Storage::disk('public')->assertExists($colonneImage);
    }

    public function test_ladmin_ajoute_une_image_a_la_galerie()
    {
        Sanctum::actingAs(Admin::factory()->create());

        $response = $this->post('/api/admin/vitrine/contenus', [
            'section' => ContenuVitrine::SECTION_GALERIE,
            'type' => ContenuVitrine::TYPE_IMAGE,
            'titre' => 'Cérémonie',
            'image' => UploadedFile::fake()->image('photo.jpg'),
        ]);

        $response->assertCreated();

        $contenu = ContenuVitrine::first();
        Storage::disk('public')->assertExists($contenu->valeur);
    }

    public function test_ladmin_ajoute_une_section_libre()
    {
        Sanctum::actingAs(Admin::factory()->create());

        $response = $this->postJson('/api/admin/vitrine/contenus', [
            'section' => ContenuVitrine::SECTION_LIBRE,
            'type' => ContenuVitrine::TYPE_TEXTE,
            'titre' => 'À propos',
            'valeur' => 'Notre histoire',
            'ordre' => 2,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('contenus_vitrine', [
            'section' => 'section',
            'titre' => 'À propos',
            'ordre' => 2,
        ]);
    }

    public function test_supprimer_un_contenu_image_efface_le_fichier()
    {
        Sanctum::actingAs(Admin::factory()->create());

        $contenu = ContenuVitrine::factory()->galerie()->create();
        Storage::disk('public')->put($contenu->valeur, 'contenu');

        $this->deleteJson("/api/admin/vitrine/contenus/{$contenu->id}")->assertOk();

        Storage::disk('public')->assertMissing($contenu->valeur);
        $this->assertDatabaseMissing('contenus_vitrine', ['id' => $contenu->id]);
    }

    public function test_un_visiteur_ne_peut_pas_modifier_la_vitrine()
    {
        $this->postJson('/api/admin/vitrine/banniere', ['titre' => 'Pirate'])
            ->assertUnauthorized();
    }
}
