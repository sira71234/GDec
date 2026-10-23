<?php

namespace App\Services;

use App\Models\Commande;

class CautionCalculatorService
{
    /**
     * Part du montant de location retenue comme caution (garantie).
     */
    private const TAUX_CAUTION = 0.30;

    /**
     * Nombre de jours de location (borné à 1 minimum).
     */
    public function nombreJours(Commande $commande): int
    {
        if (! $commande->date_debut_location || ! $commande->date_fin_location) {
            return 1;
        }

        return max(1, (int) $commande->date_debut_location->diffInDays($commande->date_fin_location) + 1);
    }

    /**
     * Montant total de la location : somme(prix_unitaire × quantité) × nombre de jours.
     */
    public function totalLocation(Commande $commande): float
    {
        $commande->loadMissing('materiels');

        $totalParJour = $commande->materiels->sum(
            fn ($materiel): float => (float) $materiel->prix_unitaire * (int) $materiel->pivot->quantite,
        );

        return round((float) $totalParJour * $this->nombreJours($commande), 2);
    }

    /**
     * Montant total de la décoration : somme(prix × quantité).
     * La quantité vaut 1 si elle n'est pas précisée sur le pivot.
     */
    public function totalDecoration(Commande $commande): float
    {
        $commande->loadMissing('prestationsDecoration');

        return round((float) $commande->prestationsDecoration->sum(
            fn ($prestation): float => (float) $prestation->prix * (int) ($prestation->pivot->quantite ?? 1),
        ), 2);
    }

    /**
     * Montant de décoration retenu : le forfait saisi par l'administrateur
     * s'il existe, sinon la somme des prestations sélectionnées.
     */
    public function totalDecorationEffectif(Commande $commande): float
    {
        if ($commande->montant_decoration !== null) {
            return round((float) $commande->montant_decoration, 2);
        }

        return $this->totalDecoration($commande);
    }

    /**
     * Montant total de la commande (location + décoration effective).
     */
    public function totalGeneral(Commande $commande): float
    {
        return round($this->totalLocation($commande) + $this->totalDecorationEffectif($commande), 2);
    }

    /**
     * Calcule le montant de la caution de la commande.
     * Formule : total de location × taux de caution.
     * Une commande « décoration seule » n'entraîne aucune caution.
     */
    public function calculerPour(Commande $commande): float
    {
        if (! in_array($commande->type_prestation, ['location', 'les_deux'], true)) {
            return 0.0;
        }

        return round($this->totalLocation($commande) * self::TAUX_CAUTION, 2);
    }
}
