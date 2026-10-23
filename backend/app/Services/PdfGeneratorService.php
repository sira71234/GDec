<?php

namespace App\Services;

use App\Models\Commande;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage; // Utilisation du gestionnaire de stockage
use Illuminate\Support\Str;

class PdfGeneratorService
{
    public function __construct(private readonly CautionCalculatorService $calculateur) {}

    /**
     * Génère le PDF récapitulatif d'une commande et le sauvegarde sur le disque.
     * Retourne le chemin relatif du fichier généré .
     */
    public function genererRecapitulatif(Commande $commande): string
    {
        // Évite le problème des requêtes N+1 dans le fichier Blade de la vue du PDF
        $commande->loadMissing(['client', 'materiels', 'prestationsDecoration', 'elementsDecor']);

        // Chargement de la vue Blade dédiée au rendu graphique du PDF
        $pdf = Pdf::loadView('pdf.recapitulatif', [
            'commande' => $commande,
            'montantLocation' => $this->calculateur->totalLocation($commande),
            'montantDecoration' => $this->calculateur->totalDecorationEffectif($commande),
            'montantTotal' => $this->calculateur->totalGeneral($commande),
        ]);

        // Génération d'un nom de fichier unique et sécurisé
        $nomFichier = 'devis/commande_'.$commande->id.'_'.Str::random(8).'.pdf';

        // SÉCURITÉ : Crée automatiquement le dossier "devis" s'il n'existe pas encore
        if (! Storage::disk('public')->exists('devis')) {
            Storage::disk('public')->makeDirectory('devis');
        }

        // Sauvegarde propre via l'API de Laravel sur le disque public
        Storage::disk('public')->put($nomFichier, $pdf->output());

        return $nomFichier;
    }

    /**
     * Génère le PDF de devis d'une commande validée et le sauvegarde sur le disque.
     * Retourne le chemin relatif du fichier généré.
     */
    public function genererDevis(Commande $commande, float $montantTotal): string
    {
        $commande->loadMissing(['client', 'materiels', 'prestationsDecoration', 'elementsDecor']);

        $pdf = Pdf::loadView('pdf.devis', [
            'commande' => $commande,
            'montantTotal' => $montantTotal,
        ]);

        $nomFichier = 'devis/devis_commande_'.$commande->id.'_'.Str::random(8).'.pdf';

        if (! Storage::disk('public')->exists('devis')) {
            Storage::disk('public')->makeDirectory('devis');
        }

        Storage::disk('public')->put($nomFichier, $pdf->output());

        return $nomFichier;
    }
}
