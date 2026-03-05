<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employe;
use Illuminate\Http\Request;

class EmployeAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'telephone' => 'required|string',
            'code_pin'  => 'required|string|min:4|max:6',
        ]);

        $employe = Employe::where('telephone', $request->telephone)
            ->where('actif', true)
            ->first();

        if (!$employe || !$employe->code_pin || $employe->code_pin !== $request->code_pin) {
            return response()->json([
                'message' => 'Téléphone ou code PIN incorrect'
            ], 401);
        }

        $token = $employe->createToken('employe-token')->plainTextToken;

        return response()->json([
            'user' => [
                'id'          => $employe->id,
                'nom'         => $employe->nom,
                'telephone'   => $employe->telephone,
                'boutique_id' => $employe->boutique_id,
                'role'        => 'employe',
                'employe_id'  => $employe->id,
            ],
            'token' => $token,
        ]);
    }
}