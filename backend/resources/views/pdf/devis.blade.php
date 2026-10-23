<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        h1 { font-size: 20px; color: #065f46; border-bottom: 2px solid #065f46; padding-bottom: 8px; }
        h2 { font-size: 14px; margin-top: 20px; color: #444; }
        .entete { margin-bottom: 15px; }
        .entete .societe { font-size: 15px; font-weight: bold; color: #065f46; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background-color: #ecfdf5; }
        .total { font-weight: bold; font-size: 16px; margin-top: 15px; text-align: right; color: #065f46; }
        .mention { margin-top: 25px; font-size: 10px; color: #777; }
    </style>
</head>
<body>
    <div class="entete">
        <div class="societe">Grace Divine Decor Services &amp; Finnes</div>
        <div>Devis n°{{ $commande->id }} — généré le {{ now()->format('d/m/Y') }}</div>
    </div>

    <h1>Devis</h1>

    <div class="info-block">
        <strong>Client :</strong> {{ $commande->client->nom }}<br>
        <strong>Téléphone :</strong> {{ $commande->client->telephone }}<br>
        @if($commande->client->email)
            <strong>Email :</strong> {{ $commande->client->email }}<br>
        @endif
        @if($commande->client->adresse)
            <strong>Adresse :</strong> {{ $commande->client->adresse }}<br>
        @endif
        <strong>Type de prestation :</strong> {{ ucfirst(str_replace('_', ' ', $commande->type_prestation)) }}
    </div>

    @if(in_array($commande->type_prestation, ['location', 'les_deux']))
        <h2>Location de matériel</h2>
        <p>
            Du {{ $commande->date_debut_location ? \Carbon\Carbon::parse($commande->date_debut_location)->format('d/m/Y') : '-' }}
            au {{ $commande->date_fin_location ? \Carbon\Carbon::parse($commande->date_fin_location)->format('d/m/Y') : '-' }}
        </p>
        @if($commande->materiels->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Matériel</th>
                        <th>Quantité</th>
                        <th>Prix unitaire</th>
                        <th>Sous-total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($commande->materiels as $materiel)
                        <tr>
                            <td>{{ $materiel->nom }}</td>
                            <td>{{ $materiel->pivot->quantite }}</td>
                            <td>{{ number_format($materiel->prix_unitaire, 0, ',', ' ') }} FCFA</td>
                            <td>{{ number_format($materiel->prix_unitaire * $materiel->pivot->quantite, 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endif

    @if(in_array($commande->type_prestation, ['decoration', 'les_deux']))
        <h2>Décoration</h2>
        @if($commande->type_evenement)
            <p>Événement : {{ $commande->type_evenement }}</p>
        @endif
        @if($commande->date_evenement)
            <p>Date : {{ \Carbon\Carbon::parse($commande->date_evenement)->format('d/m/Y') }}</p>
        @endif
        @if($commande->duree_decoration)
            <p>Durée : {{ $commande->duree_decoration }} jour(s)</p>
        @endif
        @if($commande->prestationsDecoration->isNotEmpty())
            <table>
                <thead>
                    <tr>
                        <th>Prestation</th>
                        <th>Quantité</th>
                        <th>Prix unitaire</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($commande->prestationsDecoration as $prestation)
                        @php
                            $qtyPrestation = $prestation->pivot->quantite ?? 1;
                        @endphp
                        <tr>
                            <td>{{ $prestation->nom }}</td>
                            <td>{{ $qtyPrestation }}</td>
                            <td>{{ number_format($prestation->prix, 0, ',', ' ') }} FCFA</td>
                            <td>{{ number_format($prestation->prix * $qtyPrestation, 0, ',', ' ') }} FCFA</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
        @if($commande->elementsDecor->isNotEmpty())
            <p><strong>Éléments à décorer :</strong>
                {{ $commande->elementsDecor->pluck('nom')->implode(', ') }}
            </p>
        @endif
    @endif

    <div class="total">
        Montant total : {{ number_format($montantTotal, 0, ',', ' ') }} FCFA
    </div>

    <div class="mention">
        Ce devis est valable 30 jours à compter de sa date de génération.
        Devis généré automatiquement par la plateforme de gestion des demandes.
    </div>
</body>
</html>
