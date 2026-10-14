<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ElementDecorRequest;
use App\Models\ElementDecor;
use Illuminate\Http\JsonResponse;

class AdminElementDecorController extends Controller
{
    /** GET /api/admin/elements-decor */
    public function index(): JsonResponse
    {
        return response()->json(
            ElementDecor::orderBy('nom')->get(),
        );
    }

    /** POST /api/admin/elements-decor */
    public function store(ElementDecorRequest $request): JsonResponse
    {
        $element = ElementDecor::create($request->validated());

        return response()->json([
            'message' => 'Élément de décoration ajouté.',
            'element' => $element,
        ], 201);
    }

    /** PUT /api/admin/elements-decor/{element} */
    public function update(ElementDecorRequest $request, ElementDecor $element): JsonResponse
    {
        $element->update($request->validated());

        return response()->json([
            'message' => 'Élément mis à jour.',
            'element' => $element->fresh(),
        ]);
    }

    /** DELETE /api/admin/elements-decor/{element} */
    public function destroy(ElementDecor $element): JsonResponse
    {
        $element->delete();

        return response()->json(['message' => 'Élément retiré.']);
    }
}
