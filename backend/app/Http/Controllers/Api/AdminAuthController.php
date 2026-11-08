<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AdminAuthController extends Controller
{
    /**
     * Connexion de l'administrateur (identifiant + mot de passe + code admin).
     * Un jeton Sanctum est généré pour sécuriser l'accès aux routes admin.
     *
     * POST /api/admin/login
     */
    public function login(AdminLoginRequest $request): JsonResponse
    {
        $donnees = $request->validated();

        // Trace minimale : identifiant et IP uniquement, jamais les secrets.
        // Si cette ligne n'apparaît pas dans laravel.log, la requête n'a pas
        // atteint Laravel (proxy, WAF, rewrite ou suspension de l'hébergeur).
        Log::info('Tentative de connexion admin reçue.', [
            'identifiant' => $donnees['identifiant'],
            'ip' => $request->ip(),
        ]);

        /** @var Admin|null $admin */
        $admin = Admin::where('identifiant', $donnees['identifiant'])->first();

        // Vérification stricte : le compte doit exister et les DEUX secrets
        // (mot de passe et code admin) doivent correspondre aux hachages stockés.
        if (
            ! $admin
            || ! Hash::check($donnees['mot_de_passe'], $admin->mot_de_passe)
            || ! Hash::check($donnees['code_admin'], $admin->code_admin)
        ) {
            Log::warning('Connexion admin refusée.', [
                'identifiant' => $donnees['identifiant'],
                'ip' => $request->ip(),
                'compte_trouve' => $admin !== null,
            ]);

            return response()->json([
                'message' => 'Identifiant, mot de passe ou code administrateur invalide.',
            ], 401);
        }

        $token = $admin->createToken('admin-token')->plainTextToken;

        Log::info('Connexion admin réussie.', [
            'admin_id' => $admin->id,
            'identifiant' => $admin->identifiant,
            'ip' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Connexion réussie.',
            'admin' => $admin,
            'token' => $token,
        ]);
    }

    /**
     * Retourne l'administrateur actuellement connecté.
     *
     * GET /api/admin/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    /**
     * Déconnexion : révoque le jeton utilisé pour la requête courante.
     *
     * POST /api/admin/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnexion réussie.']);
    }
}
