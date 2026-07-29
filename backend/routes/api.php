<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\CatalogueController;
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