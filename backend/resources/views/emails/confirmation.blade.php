<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.6; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #2c3e50; color: #fff; padding: 20px; text-align: center; border-radius: 4px 4px 0 0; }
        .content { padding: 20px; background-color: #f9f9f9; border-left: 1px solid #ddd; border-right: 1px solid #ddd; border-bottom: 1px solid #ddd; border-radius: 0 0 4px 4px; }
        .footer { font-size: 11px; color: #888; text-align: center; padding: 15px; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 4px; background-color: #f0ad4e; color: #fff; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2 style="margin: 0;">Confirmation de votre commande</h2>
        </div>
        <div class="content">
            <p>Bonjour <strong>{{ $commande->client->nom }}</strong>,</p>

            <p>
                Nous avons bien reçu votre demande n°<strong>{{ $commande->id }}</strong> 
                concernant {{ $commande->type_prestation === 'les_deux' ? 'la location de matériel et la décoration' : ($commande->type_prestation === 'location' ? 'la location de matériel' : 'la décoration') }} 
                de votre événement.
            </p>

            <p>
                Statut actuel de la demande : <span class="badge">{{ ucfirst(str_replace('_', ' ', $commande->statut)) }}</span>
            </p>

            <p>
                Vous trouverez en pièce jointe le récapitulatif détaillé de votre commande au format PDF. 
                Notre équipe va l'examiner et reviendra vers vous rapidement pour confirmer sa validation finale.
            </p>

            @if($commande->montant_caution > 0)
                <p style="background-color: #fff; padding: 10px; border: 1px dashed #ccc; border-radius: 4px;">
                    📌 <strong>Information de garantie :</strong> Une caution de <strong>{{ number_format($commande->montant_caution, 0, ',', ' ') }} FCFA</strong> 
                    sera à prévoir lors de la validation finale de cette commande.
                </p>
            @endif

            <p>
                Pour toute question ou modification, n'hésitez pas à nous contacter en répondant directement à cet email.
            </p>

            <p>Merci de votre confiance !<br><em>L'équipe GDec Décoration</em></p>
        </div>
        <div class="footer">
            Cet e-mail a été envoyé automatiquement suite à votre demande sur notre plateforme en accès libre. Merci de ne pas y répondre si vous n'avez pas de questions.
        </div>
    </div>
</body>
</html>
