<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CatalogueController extends Controller
{
    
    //  Retourne la liste du materiel de location dispo
    
    public function materiels(): JsonResponse
    {
        $materiels = DB::table('materiels')->get();

        return response()->json($materiels);
    }

    
    //  retourne la liste des prestations de deco dispo
    
    public function prestationsDecoration(): JsonResponse
    {
        $prestations = DB::table('prestations_decoration')->get();

        return response()->json($prestations);
    }

    
    // retourne la liste des element decor dispo 

    public function elementsDecor(): JsonResponse
    {
        $elements = DB::table('elements_decor')->get();

        return response()->json($elements);
    }

    
    //  Retourne tout le catalogue en un seul appel
    public function index(): JsonResponse
    {
        return response()->json([
            'materiels' => DB::table('materiels')->get(),
            'prestations_decoration' => DB::table('prestations_decoration')->get(),
            'elements_decor' => DB::table('elements_decor')->get(),
        ]);
    }
}