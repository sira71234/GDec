<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CatalogueController;
use App\Http\Controllers\Api\AdminCommandeController;
use App\Http\Controllers\Api\DisponibiliteController;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//  routes pour le catalogue
Route::prefix('catalogue')->group(function () { // groupe prefixe pour hierachie des urls
    Route::get('/', [CatalogueController::class, 'index']);
    Route::get('/materiels', [CatalogueController::class, 'materiels']);
    Route::get('/prestations-decoration', [CatalogueController::class, 'prestationsDecoration']);
    Route::get('/elements-decor', [CatalogueController::class, 'elementsDecor']);
});

//  routes pour l' admin

// mettre  sa a la place quand admin sera creer :
// Route::prefix('admin')->middleware('auth:admin')->group(function () {

Route::prefix('admin')->group(function () {
    // BN: route fixe toujours en premier
    Route::get('/commandes', [AdminCommandeController::class, 'index']);
    Route::get('/commandes/corbeille', [AdminCommandeController::class, 'corbeille']);
    
    // BN; Route danynique toujours après les routes fixes
    Route::get('/commandes/{commande}', [AdminCommandeController::class, 'show']);
    Route::patch('/commandes/{commande}/statut', [AdminCommandeController::class, 'updateStatut']);
    Route::patch('commandes/{id}/restaurer', [AdminCommandeController::class, 'restaurer']);
    Route::delete('commandes/{id}/definitif', [AdminCommandeController::class, 'supprimerDefinitivement']);
});

// routes pour disponibiliter
Route::get('/admin/disponibilites', [DisponibiliteController::class, 'index']);
Route::get('/disponibilites/verifier', [DisponibiliteController::class, 'verifier']);
