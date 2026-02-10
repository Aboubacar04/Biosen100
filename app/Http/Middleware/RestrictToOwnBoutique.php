<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictToOwnBoutique
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Admin a accès à tout
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Gérant : vérifier boutique_id
        $boutiqueId = $request->route('boutique')
            ?? $request->input('boutique_id')
            ?? $request->header('X-Boutique-Id');

        if ($boutiqueId && $user->boutique_id != $boutiqueId) {
            return response()->json([
                'message' => 'Accès refusé à cette boutique.'
            ], 403);
        }

        return $next($request);
    }
}
