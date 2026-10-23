<?php

namespace App\Services;

use App\Models\ActionHistorique;
use App\Models\Commande;
use Illuminate\Support\Facades\Auth;

class HistoriqueService
{
    /**
     * Enregistre une action liée à une commande, avec son auteur et ses détails.
     *
     * @param  array<string, mixed>  $details
     */
    public function enregistrer(
        Commande $commande,
        string $action,
        string $description,
        array $details = [],
    ): ActionHistorique {
        return $commande->historiques()->create([
            'admin_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'details' => $details === [] ? null : $details,
        ]);
    }

    /**
     * Trace une action qui n'est plus rattachable à une commande supprimée
     * définitivement (la clé étrangère est alors nulle).
     *
     * @param  array<string, mixed>  $details
     */
    public function enregistrerDetachee(
        string $action,
        string $description,
        array $details = [],
    ): ActionHistorique {
        return ActionHistorique::create([
            'commande_id' => null,
            'admin_id' => Auth::id(),
            'action' => $action,
            'description' => $description,
            'details' => $details === [] ? null : $details,
        ]);
    }
}
