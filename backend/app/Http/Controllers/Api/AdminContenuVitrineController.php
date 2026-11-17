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
            // FormData ne peut pas transporter une valeur nulle : le front
            // signale explicitement le retrait de l'image de fond.
            'supprimer_image' => ['nullable', 'boolean'],
        ]);

        $this->setBanniere('titre', $donnees['titre'] ?? null);
        $this->setBanniere('sous_titre', $donnees['sous_titre'] ?? null);

        $requeteImage = ContenuVitrine::where('section', ContenuVitrine::SECTION_BANNIERE)
            ->where('cle', 'image');

        if ($request->hasFile('image')) {
            $chemin = $this->images->replace($request->file('image'), $requeteImage->value('valeur'), 'vitrine');

            ContenuVitrine::updateOrCreate(
                ['section' => ContenuVitrine::SECTION_BANNIERE, 'cle' => 'image'],
                ['type' => ContenuVitrine::TYPE_IMAGE, 'valeur' => $chemin],
            );
        } elseif ($request->boolean('supprimer_image')) {
            $this->images->delete($requeteImage->value('valeur'));
            $requeteImage->delete();
        }

        return response()->json([
            'message' => 'Bannière mise à jour.',
            'vitrine' => $this->vitrine->representation(),
        ]);
    }

    /** PUT /api/admin/vitrine/reglages */
    public function updateReglages(Request $request): JsonResponse
    {
        $donnees = $request->validate([
            'afficher_visuels_defaut' => ['required', 'boolean'],
        ]);

        ContenuVitrine::updateOrCreate(
            ['section' => ContenuVitrine::SECTION_REGLAGES, 'cle' => 'afficher_visuels_defaut'],
            [
                'type' => ContenuVitrine::TYPE_TEXTE,
                'valeur' => $donnees['afficher_visuels_defaut'] ? '1' : '0',
            ],
        );

        return response()->json([
            'message' => 'Réglages de la vitrine mis à jour.',
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
