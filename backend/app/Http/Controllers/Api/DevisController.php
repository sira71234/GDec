<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Devis;
use App\Services\CautionCalculatorService;
use App\Services\PdfGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DevisController extends Controller
{
    public function __construct(
        private readonly CautionCalculatorService $calculateur,
        private readonly PdfGeneratorService $pdfGenerator,
    ) {}

    /**
     * Génère le devis PDF d'une commande validée et l'enregistre.
     *
     * POST /api/admin/commandes/{commande}/devis
     */
    public function generer(Commande $commande): JsonResponse
    {
        $montantTotal = $this->calculateur->totalLocation($commande)
            + $this->calculateur->totalDecoration($commande);

        $cheminPdf = $this->pdfGenerator->genererDevis($commande, $montantTotal);

        $devis = $commande->devis()->create([
            'fichier_pdf' => $cheminPdf,
            'montant_total' => $montantTotal,
            'date_generation' => now(),
        ]);

        return response()->json([
            'message' => 'Devis généré avec succès.',
            'devis' => $devis,
            'url' => Storage::disk('public')->url($cheminPdf),
        ], 201);
    }

    /**
     * Télécharge le fichier PDF d'un devis.
     *
     * GET /api/devis/{devis}/telecharger
     */
    public function telecharger(Devis $devis): StreamedResponse
    {
        abort_unless(
            Storage::disk('public')->exists($devis->fichier_pdf),
            404,
            'Fichier de devis introuvable.',
        );

        return Storage::disk('public')->download(
            $devis->fichier_pdf,
            'devis_commande_'.$devis->commande_id.'.pdf',
        );
    }
}
