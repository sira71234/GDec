<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SuperAdminTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): Admin
    {
        return Admin::factory()->create([
            'identifiant' => 'patron',
            'mot_de_passe' => 'motdepasse123',
            'code_admin' => '654321',
            'role' => Admin::ROLE_SUPER_ADMIN,
        ]);
    }

    private function adminOrdinaire(): Admin
    {
        return Admin::factory()->create([
            'identifiant' => 'employe',
            'mot_de_passe' => 'motdepasse123',
            'code_admin' => '111111',
            'role' => Admin::ROLE_ADMIN,
        ]);
    }

    // ==========================================================
    // CONNEXION
    // ==========================================================

    public function test_un_super_admin_peut_se_connecter_sur_la_page_super_admin()
    {
        $admin = $this->superAdmin();

        $response = $this->postJson('/api/super-admin/login', [
            'identifiant' => 'patron',
            'mot_de_passe' => 'motdepasse123',
            'code_admin' => '654321',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['message', 'admin' => ['id', 'identifiant', 'role'], 'token'])
            ->assertJsonPath('admin.role', Admin::ROLE_SUPER_ADMIN)
            ->assertJsonMissingPath('admin.mot_de_passe')
            ->assertJsonMissingPath('admin.code_admin');

        $this->assertNotNull($response->json('token'));
    }

    public function test_un_admin_ordinaire_ne_peut_pas_se_connecter_sur_la_page_super_admin()
    {
        $admin = $this->adminOrdinaire();

        $this->postJson('/api/super-admin/login', [
            'identifiant' => 'employe',
            'mot_de_passe' => 'motdepasse123',
            'code_admin' => '111111',
        ])->assertStatus(403);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_un_super_admin_peut_se_connecter_sur_la_page_admin_classique()
    {
        $this->superAdmin();

        $this->postJson('/api/admin/login', [
            'identifiant' => 'patron',
            'mot_de_passe' => 'motdepasse123',
            'code_admin' => '654321',
        ])->assertOk()
            ->assertJsonPath('admin.role', Admin::ROLE_SUPER_ADMIN);
    }

    public function test_un_mot_de_passe_errone_est_refuse_sur_la_page_super_admin()
    {
        $this->superAdmin();

        $this->postJson('/api/super-admin/login', [
            'identifiant' => 'patron',
            'mot_de_passe' => 'mauvais-secret',
            'code_admin' => '654321',
        ])->assertStatus(401);
    }

    // ==========================================================
    // ACCÈS AUX ROUTES DE GESTION
    // ==========================================================

    public function test_un_admin_ordinaire_ne_peut_pas_gerer_les_comptes()
    {
        Sanctum::actingAs($this->adminOrdinaire());

        $this->getJson('/api/super-admin/administrateurs')->assertStatus(403);
        $this->postJson('/api/super-admin/administrateurs', [])->assertStatus(403);
    }

    public function test_un_visiteur_non_connecte_ne_peut_pas_gerer_les_comptes()
    {
        $this->getJson('/api/super-admin/administrateurs')->assertStatus(401);
    }

    public function test_le_super_admin_liste_les_comptes_sans_secrets()
    {
        $patron = $this->superAdmin();
        $this->adminOrdinaire();
        Sanctum::actingAs($patron);

        $response = $this->getJson('/api/super-admin/administrateurs')
            ->assertOk()
            ->assertJsonCount(2, 'administrateurs');

        $response->assertJsonMissingPath('administrateurs.0.mot_de_passe');
        $response->assertJsonMissingPath('administrateurs.0.code_admin');
    }

    // ==========================================================
    // CRÉATION / SUPPRESSION
    // ==========================================================

    public function test_le_super_admin_cree_un_admin_ordinaire()
    {
        Sanctum::actingAs($this->superAdmin());

        $this->postJson('/api/super-admin/administrateurs', [
            'identifiant' => 'nouveau',
            'mot_de_passe' => 'motdepasse123',
            'code_admin' => '222222',
            'role' => Admin::ROLE_ADMIN,
        ])->assertCreated()
            ->assertJsonPath('administrateur.identifiant', 'nouveau')
            ->assertJsonPath('administrateur.role', Admin::ROLE_ADMIN)
            ->assertJsonMissingPath('administrateur.mot_de_passe');

        // Le compte créé fonctionne sur la page admin classique...
        $this->postJson('/api/admin/login', [
            'identifiant' => 'nouveau',
            'mot_de_passe' => 'motdepasse123',
            'code_admin' => '222222',
        ])->assertOk();

        // ... mais pas sur la page super admin.
        $this->postJson('/api/super-admin/login', [
            'identifiant' => 'nouveau',
            'mot_de_passe' => 'motdepasse123',
            'code_admin' => '222222',
        ])->assertStatus(403);
    }

    public function test_le_super_admin_cree_un_autre_super_admin()
    {
        Sanctum::actingAs($this->superAdmin());

        $this->postJson('/api/super-admin/administrateurs', [
            'identifiant' => 'patron2',
            'mot_de_passe' => 'motdepasse123',
            'code_admin' => '333333',
            'role' => Admin::ROLE_SUPER_ADMIN,
        ])->assertCreated()
            ->assertJsonPath('administrateur.role', Admin::ROLE_SUPER_ADMIN);

        $this->postJson('/api/super-admin/login', [
            'identifiant' => 'patron2',
            'mot_de_passe' => 'motdepasse123',
            'code_admin' => '333333',
        ])->assertOk();
    }

    public function test_la_creation_valide_les_champs()
    {
        Sanctum::actingAs($this->superAdmin());

        $this->postJson('/api/super-admin/administrateurs', [
            'identifiant' => 'ab',
            'mot_de_passe' => 'court',
            'code_admin' => '12',
            'role' => 'inconnu',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['identifiant', 'mot_de_passe', 'code_admin', 'role']);
    }

    public function test_lidentifiant_doit_etre_unique()
    {
        Sanctum::actingAs($this->superAdmin());
        $this->adminOrdinaire();

        $this->postJson('/api/super-admin/administrateurs', [
            'identifiant' => 'employe',
            'mot_de_passe' => 'motdepasse123',
            'code_admin' => '444444',
            'role' => Admin::ROLE_ADMIN,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['identifiant']);
    }

    public function test_le_super_admin_supprime_un_admin_et_revoque_ses_jetons()
    {
        $this->superAdmin();
        $employe = $this->adminOrdinaire();

        // On utilise de vrais jetons (et non Sanctum::actingAs) : c'est le seul
        // moyen de vérifier qu'un jeton révoqué ne fonctionne plus.
        $jetonPatron = $this->postJson('/api/super-admin/login', [
            'identifiant' => 'patron',
            'mot_de_passe' => 'motdepasse123',
            'code_admin' => '654321',
        ])->json('token');

        $jetonEmploye = $this->postJson('/api/admin/login', [
            'identifiant' => 'employe',
            'mot_de_passe' => 'motdepasse123',
            'code_admin' => '111111',
        ])->json('token');

        // Le garde Sanctum met l'utilisateur en cache entre deux requêtes du
        // même test : on le vide pour changer réellement de jeton.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$jetonEmploye)
            ->getJson('/api/admin/me')
            ->assertOk();

        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$jetonPatron)
            ->deleteJson('/api/super-admin/administrateurs/'.$employe->id)
            ->assertOk();

        $this->assertDatabaseMissing('admins', ['id' => $employe->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['tokenable_id' => $employe->id]);

        // L'ancien jeton de l'employé ne vaut plus rien.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer '.$jetonEmploye)
            ->getJson('/api/admin/me')
            ->assertStatus(401);
    }

    public function test_le_super_admin_ne_peut_pas_supprimer_son_propre_compte()
    {
        $patron = $this->superAdmin();
        Sanctum::actingAs($patron);

        $this->deleteJson('/api/super-admin/administrateurs/'.$patron->id)
            ->assertStatus(422);

        $this->assertDatabaseHas('admins', ['id' => $patron->id]);
    }

    public function test_le_dernier_super_admin_ne_peut_pas_etre_retrograde()
    {
        $patron = $this->superAdmin();
        Sanctum::actingAs($patron);

        $this->putJson('/api/super-admin/administrateurs/'.$patron->id, [
            'identifiant' => 'patron',
            'role' => Admin::ROLE_ADMIN,
        ])->assertStatus(422);

        $this->assertSame(Admin::ROLE_SUPER_ADMIN, $patron->fresh()->role);
    }

    public function test_le_super_admin_peut_changer_le_role_et_reinitialiser_les_secrets()
    {
        $patron = $this->superAdmin();
        $employe = $this->adminOrdinaire();
        Sanctum::actingAs($patron);

        $this->putJson('/api/super-admin/administrateurs/'.$employe->id, [
            'identifiant' => 'employe',
            'role' => Admin::ROLE_SUPER_ADMIN,
            'mot_de_passe' => 'nouveaumotdepasse',
            'code_admin' => '999999',
        ])->assertOk()
            ->assertJsonPath('administrateur.role', Admin::ROLE_SUPER_ADMIN);

        // Les anciens secrets ne fonctionnent plus.
        $this->postJson('/api/admin/login', [
            'identifiant' => 'employe',
            'mot_de_passe' => 'motdepasse123',
            'code_admin' => '111111',
        ])->assertStatus(401);

        // Les nouveaux oui.
        $this->postJson('/api/admin/login', [
            'identifiant' => 'employe',
            'mot_de_passe' => 'nouveaumotdepasse',
            'code_admin' => '999999',
        ])->assertOk();
    }
}
