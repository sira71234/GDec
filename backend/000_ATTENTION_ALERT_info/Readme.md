# Base de données — Backend (Laravel)

La base de données (MySQL) est gérée entièrement côté **back-end (Laravel)**. Le front-end (React) ne s'y connecte jamais directement : il passe uniquement par les endpoints de l'API (`routes/api.php`).

## ✅ Ce qui a été fait (S1 — Configuration MySQL)

- Vérification que le serveur MySQL est fonctionnel en local.
- Création de la base de données du projet :
  ```sql
  CREATE DATABASE backend CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  ```
- Configuration des variables de connexion dans le fichier `.env` :
  ```
  DB_CONNECTION=mysql
  DB_HOST=127.0.0.1
  DB_PORT=3306
  DB_DATABASE=backend
  DB_USERNAME=root
  DB_PASSWORD=
  ```
- Vérification de `config/database.php` : la connexion `mysql` utilise bien les variables du `.env` (comportement par défaut de Laravel, aucune modification nécessaire).
- Nettoyage du cache de configuration :
  ```
  php artisan config:clear
  ```
- Test de la connexion à la base :
  ```
  php artisan migrate:status
  ```
- Création du squelette du seeder du catalogue :
  ```
  php artisan make:seeder CatalogueSeeder
  ```

## 🔜 Prochaine étape (toujours S1)

- Développement des endpoints API du catalogue (`app/Http/Controllers/Api/CatalogueController.php`).
- Remplissage du `CatalogueSeeder.php` avec des données de test pour `materiels` et `prestations_decoration`, une fois les migrations correspondantes créées par Esdras.

--------------------------------------------------------
## ⚠️ Point de coordination

Les migrations et modèles (`Materiel.php`, `PrestationDecoration.php`, etc.) sont créés par **Esdras**. Il est nécessaire de se synchroniser avec lui sur :
- le nom exact de la base de données,
- les identifiants MySQL utilisés en local par toute l'équipe,
- la structure des tables avant d'écrire le seeder.
