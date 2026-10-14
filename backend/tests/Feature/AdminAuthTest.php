<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_admin_peut_se_connecter_avec_les_bons_identifiants()
    {
        Admin::factory()->create([
            'identifiant' => 'boss',
            'mot_de_passe' => 'secret123',
            'code_admin' => '654321',
        ]);

        $response = $this->postJson('/api/admin/login', [
            'identifiant' => 'boss',
            'mot_de_passe' => 'secret123',
            'code_admin' => '654321',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['message', 'admin' => ['id', 'identifiant'], 'token']);
        // Les secrets ne doivent jamais fuiter dans la réponse.
        $response->assertJsonMissingPath('admin.mot_de_passe');
        $response->assertJsonMissingPath('admin.code_admin');
    }

    public function test_un_mauvais_code_admin_renvoie_une_erreur_401()
    {
        Admin::factory()->create([
            'identifiant' => 'boss',
            'mot_de_passe' => 'secret123',
            'code_admin' => '654321',
        ]);

        $response = $this->postJson('/api/admin/login', [
            'identifiant' => 'boss',
            'mot_de_passe' => 'secret123',
            'code_admin' => '000000',
        ]);

        $response->assertStatus(401);
    }

    public function test_un_mauvais_mot_de_passe_renvoie_une_erreur_401()
    {
        Admin::factory()->create([
            'identifiant' => 'boss',
            'mot_de_passe' => 'secret123',
            'code_admin' => '654321',
        ]);

        $response = $this->postJson('/api/admin/login', [
            'identifiant' => 'boss',
            'mot_de_passe' => 'mauvais',
            'code_admin' => '654321',
        ]);

        $response->assertStatus(401);
    }

    public function test_les_champs_de_connexion_sont_obligatoires()
    {
        $response = $this->postJson('/api/admin/login', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['identifiant', 'mot_de_passe', 'code_admin']);
    }

    public function test_la_route_me_necessite_un_jeton()
    {
        $this->getJson('/api/admin/me')->assertStatus(401);

        $admin = Admin::factory()->create();
        $token = $admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/admin/me')
            ->assertOk()
            ->assertJsonPath('id', $admin->id);
    }

    public function test_un_admin_peut_se_deconnecter_et_le_jeton_est_revoque()
    {
        $admin = Admin::factory()->create();
        $token = $admin->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/admin/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
