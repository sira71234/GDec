<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Materiel;
use App\Models\PrestationDecoration;
use App\Models\ElementDecor;

class CatalogueController extends Controller
{
    /**
     * Retourne la liste du matériel disponible à la location.
     * GET /api/catalogue/materiels
     */
    public function materiels()
    {
        $materiels = Materiel::select('id', 'nom', 'description', 'photo', 'prix_unitaire', 'quantite_stock')
            ->orderBy('nom')
            ->get();

        return response()->json($materiels);
    }

    /**
     * Retourne la liste des prestations de décoration proposées.
     * GET /api/catalogue/prestations-decoration
     */
    public function prestationsDecoration()
    {
        $prestations = PrestationDecoration::select('id', 'nom', 'description', 'photo', 'prix')
            ->orderBy('nom')
            ->get();

        return response()->json($prestations);
    }

    /**
     * Retourne la liste fixe des éléments décorables
     * GET /api/catalogue/elements-decor
     */
    public function elementsDecor()
    {
        $elements = ElementDecor::select('id', 'nom', 'description')
            ->orderBy('nom')
            ->get();

        return response()->json($elements);
    }

    /**
     * Retourne tout le catalogue en un seul appel.
     * GET /api/catalogue
     */
    public function index()
    {
        return response()->json([
            'materiels' => Materiel::select('id', 'nom', 'description', 'photo', 'prix_unitaire', 'quantite_stock')
                ->orderBy('nom')->get(),
            'prestations_decoration' => PrestationDecoration::select('id', 'nom', 'description', 'photo', 'prix')
                ->orderBy('nom')->get(),
            'elements_decor' => ElementDecor::select('id', 'nom', 'description')
                ->orderBy('nom')->get(),
        ]);
    }
}