<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContenuVitrineRequest;
use App\Models\ContenuVitrine;
use App\Services\ImageStorageService;
use App\Services\VitrineService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminContenuVitrineController extends Controller
{
    public function __construct(
        private readonly VitrineService $vitrine,
        private readonly ImageStorageService $images,
    ) {}

    /** GET /api/admin/vitrine */
    public function index(): JsonResponse
    {
        return response()->json($this->vitrine->representation());
    }

    /** PUT /api/admin/vitrine/banniere */
    public function updateBanniere(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'titre' => ['nullable', 'string', 'max:255'],
            'sous_titre' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        $this->setBanniere('titre', $donnees['titre'] ?? null);
        $this->setBanniere('sous_titre', $donnees['sous_titre'] ?? null);

        if ($request->hasFile('image')) {
            $ancienne = ContenuVitrine::where('section', ContenuVitrine::SECTION_BANNIERE)
                ->where('cle', 'image')
                ->value('valeur');

            $chemin = $this->images->replace($request->file('image'), $ancienne, 'vitrine');

            ContenuVitrine::updateOrCreate(
                ['section' => ContenuVitrine::SECTION_BANNIERE, 'cle' => 'image'],
                ['type' => ContenuVitrine::TYPE_IMAGE, 'valeur' => $chemin],
            );
        }

        return response()->json([
            'message' => 'Bannière mise à jour.',
            'vitrine' => $this->vitrine->representation(),
        ]);
    }

    /** POST /api/admin/vitrine/contenus */
    public function store(ContenuVitrineRequest $request): JsonResponse
    {
        $donnees = $request->validated();

        $attributs = [
            'section' => $donnees['section'],
            'type' => $donnees['type'],
            'titre' => $donnees['titre'] ?? null,
            'valeur' => $donnees['valeur'] ?? null,
            'ordre' => $donnees['ordre'] ?? 0,
        ];

        if ($donnees['type'] === ContenuVitrine::TYPE_IMAGE) {
            $attributs['valeur'] = $this->images->store($request->file('image'), 'vitrine');
        }

        $contenu = ContenuVitrine::create($attributs);

        return response()->json([
            'message' => 'Contenu ajouté à la vitrine.',
            'contenu' => $contenu,
        ], 201);
    }

    /** PUT /api/admin/vitrine/contenus/{contenu} */
    public function update(ContenuVitrineRequest $request, ContenuVitrine $contenu): JsonResponse
    {
        $donnees = $request->validated();

        $attributs = [
            'titre' => $donnees['titre'] ?? null,
            'valeur' => $donnees['valeur'] ?? $contenu->valeur,
            'ordre' => $donnees['ordre'] ?? $contenu->ordre,
        ];

        if ($contenu->type === ContenuVitrine::TYPE_IMAGE && $request->hasFile('image')) {
            $attributs['valeur'] = $this->images->replace($request->file('image'), $contenu->valeur, 'vitrine');
        }

        $contenu->update($attributs);

        return response()->json([
            'message' => 'Contenu mis à jour.',
            'contenu' => $contenu->fresh(),
        ]);
    }

    /** DELETE /api/admin/vitrine/contenus/{contenu} */
    public function destroy(ContenuVitrine $contenu): JsonResponse
    {
        if ($contenu->type === ContenuVitrine::TYPE_IMAGE) {
            $this->images->delete($contenu->valeur);
        }

        $contenu->delete();

        return response()->json(['message' => 'Contenu retiré de la vitrine.']);
    }

    private function setBanniere(string $cle, ?string $valeur): void
    {
        ContenuVitrine::updateOrCreate(
            ['section' => ContenuVitrine::SECTION_BANNIERE, 'cle' => $cle],
            ['type' => ContenuVitrine::TYPE_TEXTE, 'valeur' => $valeur],
        );
    }
}
