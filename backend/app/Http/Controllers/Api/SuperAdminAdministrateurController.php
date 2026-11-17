<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AdminStoreRequest;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Gestion des comptes administrateurs par le super administrateur.
 * Aucune donnée secrète (mot de passe, code) n'est jamais renvoyée.
 */
class SuperAdminAdministrateurController extends Controller
{
    /** GET /api/super-admin/administrateurs */
    public function index(): JsonResponse
    {
        return response()->json([
            'administrateurs' => Admin::orderBy('identifiant')
                ->get(['id', 'identifiant', 'role', 'created_at']),
        ]);
    }

    /** POST /api/super-admin/administrateurs */
    public function store(AdminStoreRequest $request): JsonResponse
    {
        $admin = Admin::create($request->validated());

        Log::info('Administrateur créé.', [
            'admin_id' => $admin->id,
            'role' => $admin->role,
            'par' => $request->user()->identifiant,
        ]);

        return response()->json([
            'message' => 'Administrateur créé.',
            'administrateur' => $this->representation($admin),
        ], 201);
    }

    /** PUT /api/super-admin/administrateurs/{administrateur} */
    public function update(AdminStoreRequest $request, Admin $administrateur): JsonResponse
    {
        $donnees = $request->validated();

        $perdLeRoleSuper = $administrateur->estSuperAdmin()
            && $donnees['role'] !== Admin::ROLE_SUPER_ADMIN;

        if ($perdLeRoleSuper && $this->nombreSuperAdmins() <= 1) {
            return response()->json([
                'message' => 'Impossible de retirer le rôle du dernier super administrateur.',
            ], 422);
        }

        $attributs = [
            'identifiant' => $donnees['identifiant'],
            'role' => $donnees['role'],
        ];

        // Champs facultatifs en mise à jour : on ne les écrase pas avec du vide.
        if (! empty($donnees['mot_de_passe'])) {
            $attributs['mot_de_passe'] = $donnees['mot_de_passe'];
        }

        if (! empty($donnees['code_admin'])) {
            $attributs['code_admin'] = $donnees['code_admin'];
        }

        $administrateur->update($attributs);

        // Un changement de secrets doit couper les sessions ouvertes.
        if (isset($attributs['mot_de_passe']) || isset($attributs['code_admin'])) {
            $administrateur->tokens()->delete();
        }

        Log::info('Administrateur mis à jour.', [
            'admin_id' => $administrateur->id,
            'role' => $administrateur->role,
            'par' => $request->user()->identifiant,
        ]);

        return response()->json([
            'message' => 'Administrateur mis à jour.',
            'administrateur' => $this->representation($administrateur->fresh()),
        ]);
    }

    /** DELETE /api/super-admin/administrateurs/{administrateur} */
    public function destroy(Request $request, Admin $administrateur): JsonResponse
    {
        if ($administrateur->id === $request->user()->id) {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer votre propre compte.',
            ], 422);
        }

        if ($administrateur->estSuperAdmin() && $this->nombreSuperAdmins() <= 1) {
            return response()->json([
                'message' => 'Impossible de supprimer le dernier super administrateur.',
            ], 422);
        }

        // Révoque les jetons avant la suppression : l'accès est coupé net.
        $administrateur->tokens()->delete();
        $identifiant = $administrateur->identifiant;
        $administrateur->delete();

        Log::info('Administrateur supprimé.', [
            'identifiant' => $identifiant,
            'par' => $request->user()->identifiant,
        ]);

        return response()->json(['message' => 'Administrateur supprimé.']);
    }

    private function nombreSuperAdmins(): int
    {
        return Admin::where('role', Admin::ROLE_SUPER_ADMIN)->count();
    }

    /**
     * @return array{id: int, identifiant: string, role: string, created_at: ?string}
     */
    private function representation(Admin $admin): array
    {
        return [
            'id' => $admin->id,
            'identifiant' => $admin->identifiant,
            'role' => $admin->role,
            'created_at' => $admin->created_at?->toISOString(),
        ];
    }
}
