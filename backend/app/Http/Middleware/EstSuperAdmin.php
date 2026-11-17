<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Réserve les routes de gestion des comptes au super administrateur.
 * Le rôle est relu en base à chaque requête : une rétrogradation prend donc
 * effet immédiatement, sans attendre l'expiration du jeton.
 */
class EstSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $admin = $request->user();

        if (! $admin || ! method_exists($admin, 'estSuperAdmin') || ! $admin->estSuperAdmin()) {
            return response()->json([
                'message' => 'Accès réservé au super administrateur.',
            ], 403);
        }

        return $next($request);
    }
}
