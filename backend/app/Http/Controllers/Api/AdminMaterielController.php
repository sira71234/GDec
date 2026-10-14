<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\MaterielRequest;
use App\Models\Materiel;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;

class AdminMaterielController extends Controller
{
    public function __construct(private readonly ImageStorageService $images) {}

    /** GET /api/admin/materiels */
    public function index(): JsonResponse
    {
        return response()->json(
            Materiel::orderBy('nom')->get(),
        );
    }

    /** POST /api/admin/materiels */
    public function store(MaterielRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        $donnees['photo'] = $this->images->store($request->file('photo'), 'materiels');

        $materiel = Materiel::create($donnees);

        return response()->json([
            'message' => 'Matériel ajouté au catalogue.',
            'materiel' => $materiel,
        ], 201);
    }

    /** PUT /api/admin/materiels/{materiel} */
    public function update(MaterielRequest $request, Materiel $materiel): JsonResponse
    {
        $donnees = $request->validated();

        if ($request->hasFile('photo')) {
            $donnees['photo'] = $this->images->replace($request->file('photo'), $materiel->photo, 'materiels');
        }

        $materiel->update($donnees);

        return response()->json([
            'message' => 'Matériel mis à jour.',
            'materiel' => $materiel->fresh(),
        ]);
    }

    /** DELETE /api/admin/materiels/{materiel} */
    public function destroy(Materiel $materiel): JsonResponse
    {
        // Mise en corbeille (SoftDeletes) : préserve l'historique des commandes.
        $materiel->delete();

        return response()->json(['message' => 'Matériel retiré du catalogue.']);
    }
}
