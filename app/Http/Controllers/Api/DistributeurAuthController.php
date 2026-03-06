<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Distributeur;
use Illuminate\Http\Request;

class DistributeurAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string',
            'code_pin'  => 'required|string|min:4|max:6',
        ]);

        $distributeur = Distributeur::where('telephone', $request->telephone)
            ->where('actif', true)
            ->first();

        if (!$distributeur || !$distributeur->code_pin || $distributeur->code_pin !== $request->code_pin) {
            return response()->json([
                'message' => 'Téléphone ou code PIN incorrect'
            ], 401);
        }

        $token = $distributeur->createToken('distributeur-token')->plainTextToken;

        return response()->json([
            'user' => [
                'id'          => $distributeur->id,
                'nom'         => $distributeur->nom,
                'telephone'   => $distributeur->telephone,
                'boutique_id' => $distributeur->boutique_id,
                'role'        => 'distributeur',
            ],
            'token' => $token,
        ]);
    }
}