Salut Siracide, Véronique 

Les endpoints du catalogue sont prêts. Voici les URLs et le format exact des réponses pour que vous puissiez commencer ArticleCard.jsx / SectionLocation.jsx / SectionDecoration.jsx sans attendre.

GET /api/catalogue (tout en un seul appel, pratique pour la page d'accueil)

json
{
  "materiels": [
    {
      "id": 1,
      "nom": "Chaise Chiavari dorée",
      "description": "Chaise élégante pour cérémonies et réceptions",
      "photo": "materiels/chaise-chiavari.jpg",
      "prix_unitaire": "1500.00",
      "quantite_stock": 200
    }
  ],
  "prestations_decoration": [
    {
      "id": 1,
      "nom": "Arche florale",
      "description": "Arche décorée de fleurs fraîches ou artificielles",
      "photo": "prestations/arche-florale.jpg",
      "prix": "45000.00"
    }
  ],
  "elements_decor": [
    {
      "id": 1,
      "nom": "Table d'honneur",
      "description": "Table principale des mariés ou invités d'honneur"
    }
  ]
}

Endpoints séparés si vous préférez charger indépendamment :

GET /api/catalogue/materiels
GET /api/catalogue/prestations-decoration
GET /api/catalogue/elements-decor