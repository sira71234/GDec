<?php

namespace App\Mail;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

/**
 * Gère l'envoi asynchrone des e-mails de confirmation avec devis PDF joint.
 * ShouldQueue : l'envoi part en tâche de fond, le client n'attend pas le SMTP.
 */
class CommandeConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public Commande $commande;
    public string $cheminPdf;

    public function __construct(Commande $commande, string $cheminPdf)
    {
        $this->commande = $commande;
        $this->cheminPdf = $cheminPdf;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Confirmation de votre commande n°' . $this->commande->id,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.confirmation',
            with: ['commande' => $this->commande],
        );
    }

    public function attachments(): array
    {
        if (!Storage::disk('public')->exists($this->cheminPdf)) {
            return [];
        }

        return [
            Attachment::fromPath(Storage::disk('public')->path($this->cheminPdf))
                ->as('recapitulatif_commande_' . $this->commande->id . '.pdf')
                ->withMime('application/pdf'),
        ];
    }

    /**
     * Nombre de tentatives avant abandon si l'envoi échoue (ex: SMTP temporairement down).
     * Évite qu'un job reste bloqué indéfiniment ou échoue définitivement trop vite.
     */
    public int $tries = 3;

    /**
     * Délai croissant entre chaque tentative (en secondes) : 30s, puis 2min, puis 5min.
     */
    public function backoff(): array
    {
        return [30, 120, 300];
    }
}