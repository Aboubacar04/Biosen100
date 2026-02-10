<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBoutiqueActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isGerant()) {
            if (!$user->boutique || !$user->boutique->actif) {
                return response()->json([
                    'message' => 'Votre boutique a été désactivée.'
                ], 403);
            }
        }

        return $next($request);
    }
}
