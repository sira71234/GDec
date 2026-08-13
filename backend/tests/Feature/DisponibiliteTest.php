<?php

namespace Tests\Feature;

use App\Models\Commande;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisponibiliteTest extends TestCase
{
    // Nettoie et recrée la structure de base SQLite en mémoire vive à chaque test
    use RefreshDatabase;

    // ==========================================================
    // TESTS : index()
    // ==========================================================

    public function test_liste_les_reservations_location_validees_et_en_attente()
    {
        Commande::factory()->location()->create([
            'statut' => 'valide',
            'date_debut_location' => '2026-09-01',
            'date_fin_location' => '2026-09-05',
        ]);
        Commande::factory()->location()->create([
            'statut' => 'en_attente',
            'date_debut_location' => '2026-09-10',
            'date_fin_location' => '2026-09-12',
        ]);

        $response = $this->getJson('/api/admin/disponibilites');

        $response->assertOk();
        $response->assertJsonCount(2);
    }

    public function test_exclut_les_commandes_refusees()
    {
        Commande::factory()->location()->create([
            'statut' => 'refuse',
            'date_debut_location' => '2026-09-01',
            'date_fin_location' => '2026-09-05',
        ]);

        $response = $this->getJson('/api/admin/disponibilites');

        $response->assertOk();
        $response->assertJsonCount(0);
    }

    public function test_exclut_les_commandes_decoration_seule_sans_dates()
    {
        Commande::factory()->decoration()->create([
            'statut' => 'valide',
        ]);

        $response = $this->getJson('/api/admin/disponibilites');

        $response->assertOk();
        $response->assertJsonCount(0);
    }

    public function test_filtre_les_reservations_par_mois_et_annee()
    {
        Commande::factory()->location()->create([
            'statut' => 'valide',
            'date_debut_location' => '2026-09-05',
            'date_fin_location' => '2026-09-08',
        ]);
        Commande::factory()->location()->create([
            'statut' => 'valide',
            'date_debut_location' => '2026-10-05',
            'date_fin_location' => '2026-10-08',
        ]);

        $response = $this->getJson('/api/admin/disponibilites?mois=9&annee=2026');

        $response->assertOk();
        $response->assertJsonCount(1);
    }

    public function test_un_mois_invalide_renvoie_une_erreur_422()
    {
        $response = $this->getJson('/api/admin/disponibilites?mois=13&annee=2026');

        $response->assertStatus(422);
    }

    // ==========================================================
    // TESTS : verifier()
    // ==========================================================

    public function test_une_date_libre_est_bien_disponible()
    {
        Commande::factory()->location()->create([
            'statut' => 'valide',
            'date_debut_location' => '2026-09-01',
            'date_fin_location' => '2026-09-05',
        ]);

        $response = $this->getJson('/api/disponibilites/verifier?date_debut=2026-09-10&date_fin=2026-09-12');

        $response->assertOk();
        $response->assertJson(['disponible' => true]);
    }

    public function test_une_date_qui_chevauche_une_reservation_nest_pas_disponible()
    {
        Commande::factory()->location()->create([
            'statut' => 'valide',
            'date_debut_location' => '2026-09-01',
            'date_fin_location' => '2026-09-05',
        ]);

        $response = $this->getJson('/api/disponibilites/verifier?date_debut=2026-09-03&date_fin=2026-09-04');

        $response->assertOk();
        $response->assertJson(['disponible' => false]);
    }

    public function test_une_reservation_refusee_ne_bloque_pas_la_date()
    {
        Commande::factory()->location()->create([
            'statut' => 'refuse',
            'date_debut_location' => '2026-09-01',
            'date_fin_location' => '2026-09-05',
        ]);

        $response = $this->getJson('/api/disponibilites/verifier?date_debut=2026-09-03&date_fin=2026-09-04');

        $response->assertOk();
        $response->assertJson(['disponible' => true]);
    }

    public function test_une_date_fin_avant_la_date_debut_est_rejetee()
    {
        $response = $this->getJson('/api/disponibilites/verifier?date_debut=2026-09-10&date_fin=2026-09-05');

        $response->assertStatus(422);
    }
}
