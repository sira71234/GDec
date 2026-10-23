<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

class DisponibiliteController extends Controller
{
    /**
     * Liste les créneaux réservés (location et décoration), validés ou en
     * attente, pour alimenter le calendrier et la liste des réservations.
     * Le chevauchement est autorisé : plusieurs événements peuvent partager
     * une même date, le calendrier les affiche tous.
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
                    'errors' => $validator->errors(),
                ], 422);
            }
        }

        $query = Commande::with('client')
            ->whereIn('statut', ['valide', 'en_attente'])
            ->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->whereIn('type_prestation', ['location', 'les_deux'])
                        ->whereNotNull('date_debut_location')
                        ->whereNotNull('date_fin_location');
                })->orWhere(function ($q2) {
                    $q2->whereIn('type_prestation', ['decoration', 'les_deux'])
                        ->whereNotNull('date_evenement');
                });
            })
            ->orderBy('date_debut_location');

        $reservations = $query->get([
            'id',
            'client_id',
            'statut',
            'type_prestation',
            'date_debut_location',
            'date_fin_location',
            'date_evenement',
            'duree_decoration',
            'type_evenement',
        ]);

        // Filtrage par mois en PHP : gère les séjours et décorations qui
        // chevauchent les bornes du mois (début avant / fin après).
        if ($request->has('mois') && $request->has('annee')) {
            $debutMois = Carbon::createFromDate(
                (int) $request->query('annee'),
                (int) $request->query('mois'),
                1,
            )->startOfMonth();
            $finMois = $debutMois->copy()->endOfMonth();

            $reservations = $reservations
                ->filter(fn (Commande $commande): bool => $this->chevaucheMois($commande, $debutMois, $finMois))
                ->values();
        }

        return response()->json($reservations);
    }

    /**
     * Vérifie si un créneau est disponible. Le chevauchement est accepté :
     * on renvoie simplement le nombre de réservations déjà présentes sur la
     * période afin que le client en soit informé.
     *
     * GET /api/disponibilites/verifier
     */
    public function verifier(Request $request): JsonResponse
    {
        // En GET, on extrait et valide les query parameters
        $validator = Validator::make($request->query(), [
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after_or_equal:date_debut',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $params = $validator->validated();

        // Deux créneaux se chevauchent si le début de l'un est avant la fin de
        // l'autre, et vice-versa. On les compte sans bloquer la réservation.
        $chevauchements = Commande::whereIn('type_prestation', ['location', 'les_deux'])
            ->whereIn('statut', ['valide', 'en_attente'])
            ->where('date_debut_location', '<=', $params['date_fin'])
            ->where('date_fin_location', '>=', $params['date_debut'])
            ->count();

        return response()->json([
            'disponible' => true,
            'chevauchements' => $chevauchements,
        ]);
    }

    /**
     * Indique si une commande occupe au moins un jour du mois demandé.
     */
    private function chevaucheMois(Commande $commande, Carbon $debutMois, Carbon $finMois): bool
    {
        if (
            $commande->date_debut_location
            && $commande->date_fin_location
            && $commande->date_debut_location->lte($finMois)
            && $commande->date_fin_location->gte($debutMois)
        ) {
            return true;
        }

        if ($commande->date_evenement) {
            $finDecoration = $commande->date_evenement
                ->copy()
                ->addDays(max(0, (int) $commande->duree_decoration - 1));

            if ($commande->date_evenement->lte($finMois) && $finDecoration->gte($debutMois)) {
                return true;
            }
        }

        return false;
    }
}
