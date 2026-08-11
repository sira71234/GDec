<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        h1 { font-size: 18px; color: #1a1a1a; border-bottom: 2px solid #333; padding-bottom: 8px; }
        h2 { font-size: 14px; margin-top: 20px; color: #444; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .info-block { margin-bottom: 15px; }
        .total { font-weight: bold; font-size: 14px; margin-top: 10px; text-align: right; }
    </style>
</head>
<body>
    <h1>Récapitulatif de commande n°{{ $commande->id }}</h1>

    <div class="info-block">
        <strong>Client :</strong> {{ $commande->client->nom }}<br>
        <strong>Téléphone :</strong> {{ $commande->client->telephone }}<br>
        @if($commande->client->email)
            <strong>Email :</strong> {{ $commande->client->email }}<br>
        @endif
        <!-- Capitalise la première lettre pour l'affichage admin -->
        <strong>Type de prestation :</strong> {{ ucfirst(str_replace('_', ' ', $commande->type_prestation)) }}<br>
        <strong>Statut :</strong> {{ ucfirst($commande->statut) }}
    </div>

    @if(in_array($commande->type_prestation, ['location', 'les_deux']))
        <h2>Détails location</h2>
        <p>
            Du {{ \Carbon\Carbon::parse($commande->date_debut_location)->format('d/m/Y') }}
            au {{ \Carbon\Carbon::parse($commande->date_fin_location)->format('d/m/Y') }}
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
        <h2>Détails décoration</h2>
        <p>
            Événement : {{ $commande->type_evenement }}<br>
            @if($commande->theme) Thème : {{ $commande->theme }}<br> @endif
            @if($commande->couleurs) Couleurs : {{ $commande->couleurs }}<br> @endif
            Nombre de personnes : {{ $commande->nombre_personnes }}
        </p>

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
                            // Protection contre les valeurs nulles ou manquantes en BDD
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
            <h2>Éléments à décorer</h2>
            <ul>
                @foreach($commande->elementsDecor as $element)
                    <li>{{ $element->nom }}</li>
                @endforeach
            </ul>
        @endif
    @endif

    @if($commande->montant_caution > 0)
        <div class="total">
            Montant de la caution : {{ number_format($commande->montant_caution, 0, ',', ' ') }} FCFA
        </div>
    @endif

    @if($commande->complement)
        <h2>Complément d'information</h2>
        <p>{{ $commande->complement }}</p>
    @endif
</body>
</html>
