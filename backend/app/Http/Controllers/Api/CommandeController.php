<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommandeRequest;
use App\Models\Client;
use App\Models\Commande;
use App\Services\CautionCalculatorService;
use App\Services\HistoriqueService;
use App\Services\MailNotificationService;
use App\Services\PdfGeneratorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CommandeController extends Controller
{
    /**
     * Réception et enregistrement d'une commande client.
     * Enregistre le client, la commande, le matériel, la décoration et les
     * éléments sélectionnés, calcule la caution, génère le récapitulatif PDF
     * puis envoie l'email de confirmation.
     *
     * POST /api/commandes
     */
    public function store(
        StoreCommandeRequest $request,
        CautionCalculatorService $cautionCalculator,
        PdfGeneratorService $pdfGenerator,
        MailNotificationService $mailService,
        HistoriqueService $historique,
    ): JsonResponse {
        $donnees = $request->validated();

        $commande = DB::transaction(function () use ($request, $donnees, $cautionCalculator): Commande {
            // Le téléphone est l'identifiant du client : on réutilise la fiche
            // existante pour regrouper les commandes d'une même personne.
            $client = Client::where('telephone', $donnees['client']['telephone'])->first();

            $clientData = [
                'nom' => $donnees['client']['nom'],
                'telephone' => $donnees['client']['telephone'],
                'email' => $donnees['client']['email'] ?? null,
                'adresse' => $donnees['client']['adresse'] ?? null,
            ];

            if ($client) {
                $client->update($clientData);
            } else {
                $client = Client::create($clientData);
            }

            // Stockage optionnel de la pièce d'identité (location).
            $pieceIdentitePhoto = null;
            if ($request->hasFile('piece_identite_photo')) {
                $pieceIdentitePhoto = $request->file('piece_identite_photo')->store('pieces_identite', 'public');
            }

            $commande = $client->commandes()->create([
                'type_prestation' => $donnees['type_prestation'],
                'statut' => 'en_attente',
                'date_debut_location' => $donnees['date_debut_location'] ?? null,
                'date_fin_location' => $donnees['date_fin_location'] ?? null,
                'date_evenement' => $donnees['date_evenement'] ?? null,
                'duree_decoration' => $donnees['duree_decoration'] ?? null,
                'piece_identite_type' => $donnees['piece_identite_type'] ?? null,
                'piece_identite_photo' => $pieceIdentitePhoto,
                'type_evenement' => $donnees['type_evenement'] ?? null,
                'theme' => $donnees['theme'] ?? null,
                'couleurs' => $donnees['couleurs'] ?? null,
                'nombre_personnes' => $donnees['nombre_personnes'] ?? null,
                'complement' => $donnees['complement'] ?? null,
            ]);

            foreach ($donnees['materiels'] ?? [] as $ligne) {
                $commande->materiels()->attach($ligne['id'], ['quantite' => $ligne['quantite']]);
            }

            foreach ($donnees['prestations_decoration'] ?? [] as $ligne) {
                $commande->prestationsDecoration()->attach($ligne['id'], ['quantite' => $ligne['quantite'] ?? 1]);
            }

            if (! empty($donnees['elements_decor'])) {
                $commande->elementsDecor()->attach($donnees['elements_decor']);
            }

            $commande->montant_caution = $cautionCalculator->calculerPour($commande->fresh(['materiels']));
            $commande->save();

            return $commande;
        });

        // Génération du PDF et envoi de l'email hors transaction.
        $cheminPdf = $pdfGenerator->genererRecapitulatif($commande);
        $mailService->envoyerConfirmation($commande->load('client'), $cheminPdf);

        $commande->load(['client', 'materiels', 'prestationsDecoration', 'elementsDecor']);

        $historique->enregistrer(
            $commande,
            'creation',
            'Commande déposée par le client.',
            [
                'type_prestation' => $commande->type_prestation,
                'montant_caution' => $cautionCalculator->calculerPour($commande),
                'montant_total' => $cautionCalculator->totalGeneral($commande),
            ],
        );

        return response()->json([
            'message' => 'Votre commande a bien été enregistrée.',
            'commande' => array_merge($commande->toArray(), [
                'montant_location' => $cautionCalculator->totalLocation($commande),
                'montant_decoration' => $cautionCalculator->totalDecorationEffectif($commande),
                'montant_total' => $cautionCalculator->totalGeneral($commande),
            ]),
        ], 201);
    }
}
