<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Commande;
use App\Models\ElementDecor;
use App\Models\Materiel;
use App\Models\PrestationDecoration;
use App\Services\MailNotificationService;
use App\Services\PdfGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CommandeTest extends TestCase
{
    // Réinitialise la base SQLite en mémoire avant chaque test
    use RefreshDatabase;

    /**
     * Configuration initiale avant l'exécution de chaque test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public'); // Évite d'écrire physiquement les fichiers PDF
        Mail::fake();            // Évite d'envoyer de vrais e-mails sur le réseau
    }

    public function test_une_commande_location_seule_a_les_bons_champs_remplis_et_vides()
    {
        $commande = Commande::factory()->location()->create();

        $this->assertEquals('location', $commande->type_prestation);
        $this->assertNotNull($commande->date_debut_location);
        $this->assertNotNull($commande->date_fin_location);

        // Champs propres à la décoration doivent rester vides
        $this->assertNull($commande->type_evenement);
        $this->assertNull($commande->theme);
        $this->assertNull($commande->couleurs);
    }

    public function test_une_commande_location_seule_peut_avoir_du_materiel_attache()
    {
        $commande = Commande::factory()->location()->create();
        $materiel = Materiel::factory()->create(['quantite_stock' => 50]);

        $commande->materiels()->attach($materiel->id, ['quantite' => 5]);

        $this->assertCount(1, $commande->materiels()->get());
        $this->assertEquals(5, $commande->materiels()->first()->pivot->quantite);
    }

    public function test_une_commande_decoration_seule_a_les_bons_champs_remplis_et_vides()
    {
        $commande = Commande::factory()->decoration()->create();

        $this->assertEquals('decoration', $commande->type_prestation);

        // Champs propres à la location doivent rester vides
        $this->assertNull($commande->date_debut_location);
        $this->assertNull($commande->date_fin_location);
    }

    public function test_une_commande_decoration_seule_peut_avoir_des_prestations_et_elements_attaches()
    {
        $commande = Commande::factory()->decoration()->create();
        $prestation = PrestationDecoration::factory()->create();
        $element = ElementDecor::factory()->create();

        $commande->prestationsDecoration()->attach($prestation->id, ['quantite' => 2]);
        $commande->elementsDecor()->attach($element->id);

        $this->assertCount(1, $commande->prestationsDecoration()->get());
        $this->assertCount(1, $commande->elementsDecor()->get());
        $this->assertEquals(2, $commande->prestationsDecoration()->first()->pivot->quantite);
    }

    public function test_une_commande_les_deux_combine_location_et_decoration()
    {
        $commande = Commande::factory()->lesDeux()->create();
        $materiel = Materiel::factory()->create();
        $prestation = PrestationDecoration::factory()->create();

        $commande->materiels()->attach($materiel->id, ['quantite' => 3]);
        $commande->prestationsDecoration()->attach($prestation->id, ['quantite' => 1]);

        $this->assertEquals('les_deux', $commande->type_prestation);
        $this->assertCount(1, $commande->materiels()->get());
        $this->assertCount(1, $commande->prestationsDecoration()->get());
    }

    public function test_une_commande_appartient_bien_a_un_client()
    {
        $client = Client::factory()->create();
        $commande = Commande::factory()->for($client)->create();

        $this->assertEquals($client->id, $commande->client->id);
        $this->assertInstanceOf(Client::class, $commande->client);
    }

    public function test_le_statut_par_defaut_dune_nouvelle_commande_est_en_attente()
    {
        $commande = Commande::factory()->create();

        $this->assertEquals('en_attente', $commande->statut);
    }

    public function test_une_commande_supprimee_va_en_corbeille_et_nest_plus_visible_par_defaut()
    {
        $commande = Commande::factory()->create();
        $commandeId = $commande->id;

        $commande->delete();

        // Soft delete : invisible pour les requêtes de base
        $this->assertNull(Commande::find($commandeId));

        // Présent dans la corbeille
        $this->assertNotNull(Commande::onlyTrashed()->find($commandeId));
    }

    public function test_le_pdf_recapitulatif_est_genere_et_stocke_pour_une_commande_les_deux()
    {
        $commande = Commande::factory()->lesDeux()->create();
        $materiel = Materiel::factory()->create();
        $commande->materiels()->attach($materiel->id, ['quantite' => 2]);

        $service = new PdfGeneratorService();
        $chemin = $service->genererRecapitulatif($commande);

        Storage::disk('public')->assertExists($chemin);
        $this->assertStringStartsWith('devis/', $chemin);
        $this->assertStringEndsWith('.pdf', $chemin);
    }

    public function test_lemail_de_confirmation_part_bien_quand_le_client_a_un_email()
    {
        $client = Client::factory()->create(['email' => 'client@test.com']);
        $commande = Commande::factory()->for($client)->lesDeux()->create();

        $pdfService = new PdfGeneratorService();
        $chemin = $pdfService->genererRecapitulatif($commande);

        $mailService = new MailNotificationService();
        $resultat = $mailService->envoyerConfirmation($commande, $chemin);

        $this->assertTrue($resultat);
        Mail::assertQueued(\App\Mail\CommandeConfirmationMail::class, function ($mail) use ($commande) {
        return $mail->commande->id === $commande->id;
    });
    
    }

    public function test_lemail_de_confirmation_ne_part_pas_si_le_client_na_pas_demail()
    {
        $client = Client::factory()->create(['email' => null]);
        $commande = Commande::factory()->for($client)->create();

        $mailService = new MailNotificationService();
        $resultat = $mailService->envoyerConfirmation($commande, 'devis/fichier_test.pdf');

        $this->assertFalse($resultat);
        Mail::assertNothingSent();
    }
}
