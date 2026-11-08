<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Devis;
use App\Services\CautionCalculatorService;
use App\Services\HistoriqueService;
use App\Services\PdfGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DevisController extends Controller
{
    public function __construct(
        private readonly CautionCalculatorService $calculateur,
        private readonly PdfGeneratorService $pdfGenerator,
        private readonly HistoriqueService $historique,
    ) {}

    /**
     * Génère le devis PDF d'une commande validée et l'enregistre.
     *
     * POST /api/admin/commandes/{commande}/devis
     */
    public function generer(Commande $commande): JsonResponse
    {
        $montantTotal = $this->calculateur->totalGeneral($commande);

        $cheminPdf = $this->pdfGenerator->genererDevis($commande, $montantTotal);

        $devis = $commande->devis()->create([
            'fichier_pdf' => $cheminPdf,
            'montant_total' => $montantTotal,
            'date_generation' => now(),
        ]);

        $this->historique->enregistrer(
            $commande,
            'devis',
            'Devis généré par l\'administrateur.',
            ['devis_id' => $devis->id, 'montant_total' => $montantTotal],
        );

        return response()->json([
            'message' => 'Devis généré avec succès.',
            'devis' => $devis,
            'url' => url("/api/devis/{$devis->id}/telecharger"),
        ], 201);
    }

    /**
     * Télécharge le fichier PDF d'un devis.
     *
     * GET /api/devis/{devis}/telecharger
     */
    public function telecharger(Devis $devis): StreamedResponse
    {
        // Rétrocompatibilité : les anciens devis peuvent encore se trouver
        // sur le disque public. Les nouveaux sont écrits sur le disque privé.
        $disque = Storage::disk('local')->exists($devis->fichier_pdf)
            ? 'local'
            : 'public';

        abort_unless(
            Storage::disk($disque)->exists($devis->fichier_pdf),
            404,
            'Fichier de devis introuvable.',
        );

        return Storage::disk($disque)->download(
            $devis->fichier_pdf,
            'devis_commande_'.$devis->commande_id.'.pdf',
        );
    }
}
