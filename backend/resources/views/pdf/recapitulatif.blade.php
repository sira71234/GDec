<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Récapitulatif de commande</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 13px; color: #333; }
        h1 { font-size: 18px; margin-bottom: 5px; }
        .section { margin-top: 20px; }
        .section-title { font-size: 15px; font-weight: bold; border-bottom: 1px solid #ccc; padding-bottom: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #f4f4f4; }
        .total { font-weight: bold; margin-top: 15px; }
    </style>
</head>
<body>
    <h1>Récapitulatif de commande n°{{ $commande->id }}</h1>
    <p>Client : {{ $commande->client->nom }} — {{ $commande->client->telephone }}</p>
    <p>Statut : {{ $commande->statut }}</p>

    @if($commande->type_prestation === 'location' || $commande->type_prestation === 'les_deux')
        <div class="section">
            <div class="section-title">Matériel loué</div>
            <table>
                <thead>
                    <tr><th>Article</th><th>Quantité</th><th>Prix unitaire</th></tr>
                </thead>
                <tbody>
                    @foreach($commande->materiels as $ligne)
                        <tr>
                            <td>{{ $ligne->materiel->nom }}</td>
                            <td>{{ $ligne->quantite }}</td>
                            <td>{{ $ligne->materiel->prix_unitaire }} FCFA</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p>Période : du {{ $commande->date_debut_location }} au {{ $commande->date_fin_location }}</p>
        </div>
    @endif

    @if($commande->type_prestation === 'decoration' || $commande->type_prestation === 'les_deux')
        <div class="section">
            <div class="section-title">Prestations de décoration</div>
            <table>
                <thead>
                    <tr><th>Prestation</th><th>Prix</th></tr>
                </thead>
                <tbody>
                    @foreach($commande->decorations as $ligne)
                        <tr>
                            <td>{{ $ligne->prestation->nom }}</td>
                            <td>{{ $ligne->prestation->prix }} FCFA</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="section total">
        Montant de la caution : {{ $commande->montant_caution ?? 'Non calculé' }} FCFA
    </div>
</body>
</html>