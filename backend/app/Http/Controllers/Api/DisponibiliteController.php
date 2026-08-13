<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DisponibiliteController extends Controller
{
    /**
     * Liste les créneaux de location déjà réservés (validés + en attente).
     * alimente CalendrierDisponibilite.jsx et ListeDisponibilite.jsx.
     * 
     * GET /api/admin/disponibilites
     */
    public function index(Request $request): JsonResponse
    {
        // Validation spécifique pour les paramètres GET de l'URL
        if ($request->has('mois') && $request->has('annee')) {
            $validator = Validator::make($request->query(), [
                'mois' => 'required|integer|min:1|max:12',
                'annee' => 'required|integer|min:2020|max:2100',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 422);
            }
        }

        //  Construction de la requête de filtrage
        $query = Commande::with('client')
            ->whereIn('type_prestation', ['location', 'les_deux'])
            ->whereIn('statut', ['valide', 'en_attente'])
            ->whereNotNull('date_debut_location')
            ->whereNotNull('date_fin_location')
            ->orderBy('date_debut_location');

        // Application du filtre par mois si les données sont validées
        if ($request->has('mois') && $request->has('annee')) {
            $query->whereYear('date_debut_location', $request->query('annee'))
                  ->whereMonth('date_debut_location', $request->query('mois'));
        }

        // Extraction des colonnes légères requises par le calendrier
        $reservations = $query->get([
            'id',
            'client_id',
            'statut',
            'date_debut_location',
            'date_fin_location',
            'type_evenement',
        ]);

        return response()->json($reservations);
    }

    /**
     * Vérifie si un créneau précis est disponible.
     * Appelé par le formulaire client (Commande.jsx) avant soumission.
     * 
     * GET /api/disponibilites/verifier
     */
    public function verifier(Request $request): JsonResponse
    {
        // En GET, on extrait et valide les query parameters
        $validator = Validator::make($request->query(), [
            'date_debut' => 'required|date',
            'date_fin'   => 'required|date|after_or_equal:date_debut',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors'  => $validator->errors()
            ], 422);
        }

        $params = $validator->validated();

        // deux créneaux se chevauchent sile début de l'un est avant la fin de l'autre, et vice-versa.
        $conflit = Commande::whereIn('type_prestation', ['location', 'les_deux'])
            ->whereIn('statut', ['valide', 'en_attente'])
            ->where('date_debut_location', '<=', $params['date_fin'])
            ->where('date_fin_location', '>=', $params['date_debut'])
            ->exists();

        return response()->json([
            'disponible' => !$conflit,
        ]);
    }
}
