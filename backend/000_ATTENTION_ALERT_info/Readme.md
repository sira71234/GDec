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

2. **Création des tables (migrations)**
   Toutes les tables du projet ont été créées via des migrations Laravel :

   - `clients`
   - `materiels`
   - `prestations_decoration`
   - `elements_decor`
   - `admins`
   - `commandes`
   - `commande_materiel`
   - `commande_decoration`
   - `commande_elements_decor`
   - `devis`

   Ces tables respectent la structure définie dans le **dictionnaire de données** du projet, avec les bonnes relations entre elles (clés étrangères).

3. **Exécution des migrations**
   ```
   php artisan migrate
   ```
   Toutes les tables ont été créées avec succès dans la base de données.