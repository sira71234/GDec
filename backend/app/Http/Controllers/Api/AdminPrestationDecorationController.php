<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrestationDecorationRequest;
use App\Models\PrestationDecoration;
use App\Services\ImageStorageService;
use Illuminate\Http\JsonResponse;

class AdminPrestationDecorationController extends Controller
{
    public function __construct(private readonly ImageStorageService $images) {}

    /** GET /api/admin/prestations-decoration */
    public function index(): JsonResponse
    {
        return response()->json(
            PrestationDecoration::orderBy('nom')->get(),
        );
    }

    /** POST /api/admin/prestations-decoration */
    public function store(PrestationDecorationRequest $request): JsonResponse
    {
        $donnees = $request->validated();
        $donnees['photo'] = $this->images->store($request->file('photo'), 'prestations');

        $prestation = PrestationDecoration::create($donnees);

        return response()->json([
            'message' => 'Prestation ajoutée au catalogue.',
            'prestation' => $prestation,
        ], 201);
    }

    /** PUT /api/admin/prestations-decoration/{prestation} */
    public function update(PrestationDecorationRequest $request, PrestationDecoration $prestation): JsonResponse
    {
        $donnees = $request->validated();

        if ($request->hasFile('photo')) {
            $donnees['photo'] = $this->images->replace($request->file('photo'), $prestation->photo, 'prestations');
        }

        $prestation->update($donnees);

        return response()->json([
            'message' => 'Prestation mise à jour.',
            'prestation' => $prestation->fresh(),
        ]);
    }

    /** DELETE /api/admin/prestations-decoration/{prestation} */
    public function destroy(PrestationDecoration $prestation): JsonResponse
    {
        $prestation->delete();

        return response()->json(['message' => 'Prestation retirée du catalogue.']);
    }
}
