<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Services\CautionCalculatorService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminCommandeController extends Controller
{
    public function __construct(private readonly CautionCalculatorService $calculateur) {}

    /**
     * Liste les commandes pour le tableau de bord admin, avec filtre optionnel par statut.
     * Accessible par le composant ListeDemandes.jsx de Siracide.
     *
     * GET /api/admin/commandes
     * GET /api/admin/commandes?statut=en_attente
     */
    public function index(Request $request): JsonResponse
    {
        // Validation du filtre de statut si présent dans l'URL
        if ($request->has('statut')) {
            $validator = Validator::make($request->all(), [
                'statut' => 'string|in:en_attente,valide,refuse',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors' => $validator->errors(),
                ], 422);
            }
        }

        // Construction de la requête avec chargement lié du client (Évite le bug N+1)
        $query = Commande::with('client')
            ->orderBy('created_at', 'desc');

        // Application du filtre si validé
        if ($request->has('statut')) {
            $query->where('statut', (string) $request->query('statut'));
        }

        // Pagination stricte à 20 résultats par page
        $commandes = $query->paginate(20);

        // Retour JSON structuré pour le Frontend React
        return response()->json($commandes, 200);
    }

    /**
     * Affiche le détail complet d'une commande, avec toutes ses relations.
     * Utile pour la vue détaillée d'une demande côté admin.
     *
     * GET /api/admin/commandes/{id}
     */
    public function show(Commande $commande): JsonResponse
    {
        // Chargement à la demande des relations requises par le Front
        $commande->loadMissing([
            'client',
            'materiels',
            'prestationsDecoration',
            'elementsDecor',
            'devis',
        ]);

        // Retour JSON de la commande complète avec ses relations imbriquées
        return response()->json($this->avecTotaux($commande), 200);
    }

    /**
     * Modifie une commande avant la génération du devis : montant de la
     * décoration, dates (location et événement) et statut.
     *
     * PUT/PATCH /api/admin/commandes/{commande}
     */
    public function update(Request $request, Commande $commande): JsonResponse
    {
        $donnees = $request->validate([
            'statut' => ['sometimes', 'in:en_attente,valide,refuse'],
            'date_debut_location' => ['sometimes', 'nullable', 'date'],
            'date_fin_location' => ['sometimes', 'nullable', 'date', 'after_or_equal:date_debut_location'],
            'date_evenement' => ['sometimes', 'nullable', 'date'],
            'duree_decoration' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'montant_decoration' => ['sometimes', 'nullable', 'numeric', 'min:0'],
        ]);

        $commande->update($donnees);

        // La caution dépend des dates de location : on la recalcule.
        $commande->montant_caution = $this->calculateur->calculerPour($commande->fresh(['materiels']));
        $commande->save();

        return response()->json([
            'message' => 'Commande mise à jour.',
            'commande' => $this->avecTotaux($commande->fresh([
                'client', 'materiels', 'prestationsDecoration', 'elementsDecor', 'devis',
            ])),
        ]);
    }

    /**
     * Ajoute les montants calculés au modèle avant sérialisation JSON.
     *
     * @return array<string, mixed>
     */
    private function avecTotaux(Commande $commande): array
    {
        return array_merge($commande->toArray(), [
            'montant_location' => $this->calculateur->totalLocation($commande),
            'montant_decoration_calcule' => $this->calculateur->totalDecoration($commande),
            'montant_decoration_effectif' => $this->calculateur->totalDecorationEffectif($commande),
            'montant_total' => $this->calculateur->totalGeneral($commande),
        ]);
    }

    /**
     * Met à jour le statut d'une commande (en_attente / valide / refuse).
     * PATCH /api/admin/commandes/{id}/statut
     */
    public function updateStatut(Request $request, Commande $commande)
    {
        // Validation de l'entrée pour le tableau de bord
        $validated = $request->validate(['statut' => 'required|in:en_attente,valide,refuse']);

        // Mise à jour simple en BDD
        $commande->update(['statut' => $validated['statut']]);

        // 3. Retour JSON avec le modèle client rafraîchi
        return response()->json([
            'message' => 'Statut mis à jour avec succès.',
            'commande' => $commande->fresh(['client']),
        ]);
    }

    /**
     * Liste les commandes supprimées (en corbeille), non visibles via index().
     * GET /api/admin/commandes/corbeille
     */
    public function corbeille(): JsonResponse
    {
        $commandes = Commande::onlyTrashed()
            ->with('client')
            ->orderBy('deleted_at', 'desc')
            ->paginate(20);

        return response()->json($commandes);
    }

    /**
     * Restaure une commande depuis la corbeille.
     * PATCH /api/admin/commandes/{id}/restaurer
     */
    public function restaurer($id)
    {
        // withTrashed() : sinon Laravel ne trouve pas la ligne (elle est masquée)
        $commande = Commande::withTrashed()->findOrFail($id);
        $commande->restore();

        return response()->json(['message' => 'Commande restaurée']);
    }

    /**
     * Supprime définitivement une commande (irréversible).
     * DELETE /api/admin/commandes/{id}/definitif
     */
    public function supprimerDefinitivement($id)
    {
        $commande = Commande::withTrashed()->findOrFail($id);

        // Sécurité on force le check avant le vrai DELETE
        try {
            $commande->forceDelete();
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Impossible : des données liées existent encore.',
            ], 409);
        }

        return response()->json(['message' => 'Commande supprimée définitivement']);
    }
}
