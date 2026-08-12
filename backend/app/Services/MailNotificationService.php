<?php

namespace App\Services;

use App\Mail\CommandeConfirmationMail;
use App\Models\Commande;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Throwable; // Import de la classe de base pour la capture des erreurs

class MailNotificationService
{
    /**
     * Déclenche l'envoi de l'e-mail de confirmation au client.
     * L'e-mail est automatiquement mis en file d'attente grâce à ShouldQueue.
     *
     * @param Commande $commande La commande concernée.
     * @param string $cheminPdf Chemin relatif du fichier devis PDF.
     * @return bool True si l'e-mail a été pris en charge, False sinon.
     */
    public function envoyerConfirmation(Commande $commande, string $cheminPdf): bool
    {
        //  Sécurité : Vérification si le client possède bien une adresse e-mail
        if (empty($commande->client->email)) {
            Log::warning('Envoi email annulé : aucun email renseigné pour le client', [
                'commande_id' => $commande->id,
                'client_id'   => $commande->client->id,
            ]);
            return false;
        }

        try {
            //  Envoi de l'e-mail asynchrone via la file d'attente (Queue)
            Mail::to($commande->client->email)
                ->send(new CommandeConfirmationMail($commande, $cheminPdf));

            return true;
        } catch (Throwable $e) {
            //  Sécurité Production : Journalisation en cas de crash du pilote d'envoi
            Log::error('Échec de la mise en file d\'attente de l\'email de confirmation', [
                'commande_id' => $commande->id,
                'erreur'      => $e->getMessage(),
            ]);
            return false;
        }
    }
    
}
