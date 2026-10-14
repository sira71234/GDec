<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminLoginRequest;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

        /** @var Admin|null $admin */
        $admin = Admin::where('identifiant', $donnees['identifiant'])->first();

        // Vérification stricte : le compte doit exister et les DEUX secrets
        // (mot de passe et code admin) doivent correspondre aux hachages stockés.
        if (
            ! $admin
            || ! Hash::check($donnees['mot_de_passe'], $admin->mot_de_passe)
            || ! Hash::check($donnees['code_admin'], $admin->code_admin)
        ) {
            return response()->json([
                'message' => 'Identifiant, mot de passe ou code administrateur invalide.',
            ], 401);
        }

        $token = $admin->createToken('admin-token')->plainTextToken;

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
