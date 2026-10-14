<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\VitrineService;
use Illuminate\Http\JsonResponse;

class VitrineController extends Controller
{
    public function __construct(private readonly VitrineService $vitrine) {}

    /**
     * Contenu public de la vitrine (page d'accueil).
     * GET /api/vitrine
     */
    public function index(): JsonResponse
    {
        return response()->json($this->vitrine->representation());
    }
}
