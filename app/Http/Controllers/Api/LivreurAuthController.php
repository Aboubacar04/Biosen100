<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Livreur;
use Illuminate\Http\Request;

class LivreurAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string',
            'code_pin'  => 'required|string|min:4|max:6',
        ]);

        $livreur = Livreur::where('telephone', $request->telephone)
            ->where('actif', true)
            ->first();

        if (!$livreur || !$livreur->code_pin || $livreur->code_pin !== $request->code_pin) {
            return response()->json([
                'message' => 'Téléphone ou code PIN incorrect'
            ], 401);
        }

        $token = $livreur->createToken('livreur-token')->plainTextToken;

        return response()->json([
            'user' => [
                'id'          => $livreur->id,
                'nom'         => $livreur->nom,
                'telephone'   => $livreur->telephone,
                'boutique_id' => $livreur->boutique_id,
                'role'        => 'livreur',
                'livreur_id'  => $livreur->id,
            ],
            'token' => $token,
        ]);
    }
}