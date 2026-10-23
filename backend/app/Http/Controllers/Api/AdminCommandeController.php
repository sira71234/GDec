<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Services\CautionCalculatorService;
use App\Services\HistoriqueService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminCommandeController extends Controller
{
    public function __construct(
        private readonly CautionCalculatorService $calculateur,
        private readonly HistoriqueService $historique,
    ) {}

    /**
     * Liste les commandes actives pour le tableau de bord admin, avec filtre
     * optionnel par statut. Les commandes dont la date est dépassée de plus de
     * deux jours sont exclues (elles restent consultables dans l'historique).
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
        $query = Commande::with(['client', 'derniereAction.admin'])
            ->actives()
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
     * Liste les commandes passées (date dépassée de plus de deux jours) :
     * elles constituent l'historique consultable des demandes.
     *
     * GET /api/admin/commandes/historique
     */
    public function historique(Request $request): JsonResponse
    {
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

        $query = Commande::with(['client', 'derniereAction.admin'])
            ->historiques()
            ->orderByDesc('date_evenement')
            ->orderByDesc('date_fin_location');

        if ($request->has('statut')) {
            $query->where('statut', (string) $request->query('statut'));
        }

        return response()->json($query->paginate(20), 200);
    }

    /**
     * Affiche le détail complet d'une commande, avec ses relations et son
     * journal d'actions.
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
            'historiques.admin',
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

        $champs = array_keys($donnees);
        $avant = $commande->only($champs);

        $commande->update($donnees);

        // La caution dépend des dates de location : on la recalcule.
        $commande->montant_caution = $this->calculateur->calculerPour($commande->fresh(['materiels']));
        $commande->save();

        $changements = [];
        foreach ($donnees as $champ => $valeur) {
            $ancienne = $avant[$champ] ?? null;
            if ((string) $ancienne !== (string) $valeur) {
                $changements[$champ] = ['avant' => $ancienne, 'apres' => $valeur];
            }
        }

        $this->historique->enregistrer(
            $commande,
            'modification',
            "Commande modifiée par l'administrateur.",
            ['changements' => $changements],
        );

        return response()->json([
            'message' => 'Commande mise à jour.',
            'commande' => $this->avecTotaux($commande->fresh([
                'client', 'materiels', 'prestationsDecoration', 'elementsDecor', 'devis', 'historiques.admin',
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

        $ancienStatut = $commande->statut;

        // Mise à jour simple en BDD
        $commande->update(['statut' => $validated['statut']]);

        $this->historique->enregistrer(
            $commande,
            'changement_statut',
            "Statut modifié : {$ancienStatut} → {$validated['statut']}.",
            ['avant' => $ancienStatut, 'apres' => $validated['statut']],
        );

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

        $this->historique->enregistrer(
            $commande,
            'restauration',
            'Commande restaurée depuis la corbeille.',
        );

        return response()->json(['message' => 'Commande restaurée']);
    }

    /**
     * Supprime définitivement une commande (irréversible).
     * DELETE /api/admin/commandes/{id}/definitif
     */
    public function supprimerDefinitivement($id)
    {
        $commande = Commande::withTrashed()->findOrFail($id);

        $details = [
            'commande_id' => $commande->id,
            'client_id' => $commande->client_id,
            'type_prestation' => $commande->type_prestation,
            'statut' => $commande->statut,
        ];

        // Sécurité on force le check avant le vrai DELETE
        try {
            $commande->forceDelete();
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Impossible : des données liées existent encore.',
            ], 409);
        }

        $this->historique->enregistrerDetachee(
            'suppression_definitive',
            "Commande #{$details['commande_id']} supprimée définitivement.",
            $details,
        );

        return response()->json(['message' => 'Commande supprimée définitivement']);
    }
}
