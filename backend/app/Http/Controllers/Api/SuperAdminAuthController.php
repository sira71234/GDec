<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Connexion dédiée au super administrateur.
 *
 * Un compte ordinaire peut se connecter sur la page admin classique, mais
 * seul un super administrateur franchit cette porte. En revanche, un super
 * administrateur peut aussi utiliser la page admin classique.
 */
class SuperAdminAuthController extends Controller
{
    /**
     * POST /api/super-admin/login
     */
    public function login(AdminLoginRequest $request): JsonResponse
    {
        // Sans la colonne "role", impossible de distinguer les comptes :
        // on renvoie un message explicite plutôt qu'une erreur 500.
        if (! Schema::hasColumn('admins', 'role')) {
            return response()->json([
                'message' => "La base de données n'est pas à jour. Exécutez la migration du rôle des administrateurs.",
            ], 503);
        }

        $donnees = $request->validated();

        Log::info('Tentative de connexion super admin reçue.', [
            'identifiant' => $donnees['identifiant'],
            'ip' => $request->ip(),
        ]);

        /** @var Admin|null $admin */
        $admin = Admin::where('identifiant', $donnees['identifiant'])->first();

        if (
            ! $admin
            || ! Hash::check($donnees['mot_de_passe'], $admin->mot_de_passe)
            || ! Hash::check($donnees['code_admin'], $admin->code_admin)
        ) {
            Log::warning('Connexion super admin refusée.', [
                'identifiant' => $donnees['identifiant'],
                'ip' => $request->ip(),
                'compte_trouve' => $admin !== null,
            ]);

            return response()->json([
                'message' => 'Identifiant, mot de passe ou code administrateur invalide.',
            ], 401);
        }

        // Les secrets sont corrects : on peut répondre franchement sur le rôle,
        // sans risque d'énumération de comptes.
        if (! $admin->estSuperAdmin()) {
            Log::warning('Connexion super admin refusée : rôle insuffisant.', [
                'admin_id' => $admin->id,
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => "Ce compte n'est pas super administrateur.",
            ], 403);
        }

        $token = $admin->createToken('super-admin-token')->plainTextToken;

        Log::info('Connexion super admin réussie.', [
            'admin_id' => $admin->id,
            'identifiant' => $admin->identifiant,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Connexion super administrateur réussie.',
            'admin' => $admin,
            'token' => $token,
        ]);
    }

    /**
     * GET /api/super-admin/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
