<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Superviseur;
use Illuminate\Http\Request;

class SuperviseurAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string',
            'code_pin'  => 'required|string',
        ]);

        $superviseur = Superviseur::where('telephone', $request->telephone)
            ->where('actif', true)
            ->first();

        if (!$superviseur || $superviseur->code_pin !== $request->code_pin) {
            return response()->json(['message' => 'Identifiants incorrects'], 401);
        }

        $token = $superviseur->createToken('superviseur-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user'  => [
                'id'          => $superviseur->id,
                'nom'         => $superviseur->nom,
                'telephone'   => $superviseur->telephone,
                'boutique_id' => $superviseur->boutique_id,
                'role'        => 'superviseur',
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Déconnexion réussie']);
    }
}