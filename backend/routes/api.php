<?php

use App\Http\Controllers\Api\AdminAuthController;
use App\Http\Controllers\Api\AdminCommandeController;
use App\Http\Controllers\Api\AdminContenuVitrineController;
use App\Http\Controllers\Api\AdminElementDecorController;
use App\Http\Controllers\Api\AdminMaterielController;
use App\Http\Controllers\Api\AdminPrestationDecorationController;
use App\Http\Controllers\Api\CatalogueController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\DevisController;
use App\Http\Controllers\Api\DisponibiliteController;
use App\Http\Controllers\Api\VitrineController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Routes publiques du catalogue.
Route::prefix('catalogue')->group(function () {
    Route::get('/', [CatalogueController::class, 'index']);
    Route::get('/materiels', [CatalogueController::class, 'materiels']);
    Route::get('/prestations-decoration', [CatalogueController::class, 'prestationsDecoration']);
    Route::get('/elements-decor', [CatalogueController::class, 'elementsDecor']);
});

// Dépôt d'une commande par le client (public).
Route::post('/commandes', [CommandeController::class, 'store']);

// Contenu public de la vitrine (page d'accueil).
Route::get('/vitrine', [VitrineController::class, 'index']);

// Vérification de disponibilité d'un créneau (utilisée par le formulaire client).
Route::get('/disponibilites/verifier', [DisponibiliteController::class, 'verifier']);

// Connexion administrateur (identifiant + mot de passe + code admin).
Route::post('/admin/login', [AdminAuthController::class, 'login']);

// Routes protégées par jeton Sanctum (espace administrateur).
Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('admin')->group(function () {
        Route::post('/logout', [AdminAuthController::class, 'logout']);
        Route::get('/me', [AdminAuthController::class, 'me']);

        // Gestion des demandes.
        Route::get('/commandes', [AdminCommandeController::class, 'index']);
        // Route fixe avant la route dynamique {commande}.
        Route::get('/commandes/corbeille', [AdminCommandeController::class, 'corbeille']);
        Route::get('/commandes/{commande}', [AdminCommandeController::class, 'show']);
        Route::match(['put', 'patch', 'post'], '/commandes/{commande}', [AdminCommandeController::class, 'update']);
        Route::patch('/commandes/{commande}/statut', [AdminCommandeController::class, 'updateStatut']);
        Route::patch('/commandes/{id}/restaurer', [AdminCommandeController::class, 'restaurer']);
        Route::delete('/commandes/{id}/definitif', [AdminCommandeController::class, 'supprimerDefinitivement']);

        // Génération du devis d'une commande.
        Route::post('/commandes/{commande}/devis', [DevisController::class, 'generer']);

        // Disponibilités (vue calendrier / liste des réservations).
        Route::get('/disponibilites', [DisponibiliteController::class, 'index']);

        // --- Catalogue : gestion par l'administrateur ---
        // Les mises à jour acceptent POST en plus de PUT/PATCH afin de supporter
        // l'upload de fichier multipart (PHP ne peuple pas $_FILES sur un vrai PUT).
        Route::get('/materiels', [AdminMaterielController::class, 'index']);
        Route::post('/materiels', [AdminMaterielController::class, 'store']);
        Route::match(['put', 'patch', 'post'], '/materiels/{materiel}', [AdminMaterielController::class, 'update']);
        Route::delete('/materiels/{materiel}', [AdminMaterielController::class, 'destroy']);

        Route::get('/prestations-decoration', [AdminPrestationDecorationController::class, 'index']);
        Route::post('/prestations-decoration', [AdminPrestationDecorationController::class, 'store']);
        Route::match(['put', 'patch', 'post'], '/prestations-decoration/{prestation}', [AdminPrestationDecorationController::class, 'update']);
        Route::delete('/prestations-decoration/{prestation}', [AdminPrestationDecorationController::class, 'destroy']);

        Route::get('/elements-decor', [AdminElementDecorController::class, 'index']);
        Route::post('/elements-decor', [AdminElementDecorController::class, 'store']);
        Route::match(['put', 'patch', 'post'], '/elements-decor/{element}', [AdminElementDecorController::class, 'update']);
        Route::delete('/elements-decor/{element}', [AdminElementDecorController::class, 'destroy']);

        // --- Vitrine : textes et images de la page d'accueil ---
        Route::get('/vitrine', [AdminContenuVitrineController::class, 'index']);
        Route::match(['put', 'patch', 'post'], '/vitrine/banniere', [AdminContenuVitrineController::class, 'updateBanniere']);
        Route::post('/vitrine/contenus', [AdminContenuVitrineController::class, 'store']);
        Route::match(['put', 'patch', 'post'], '/vitrine/contenus/{contenu}', [AdminContenuVitrineController::class, 'update']);
        Route::delete('/vitrine/contenus/{contenu}', [AdminContenuVitrineController::class, 'destroy']);
    });

    // Téléchargement d'un devis généré.
    Route::get('/devis/{devis}/telecharger', [DevisController::class, 'telecharger']);
});
