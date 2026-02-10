<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->actif) {
            $user->tokens()->delete();

            return response()->json([
                'message' => 'Votre compte a été désactivé.'
            ], 403);
        }

        return $next($request);
    }
}
