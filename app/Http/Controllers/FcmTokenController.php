<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'token' => 'required|string|max:500',
            'role'  => 'required|string|in:admin,employe,livreur,distributeur,superviseur',
        ]);

        $user = $request->user();

        FcmToken::updateOrCreate(
            ['token' => $request->token],
            [
                'role'        => $request->role,
                'user_id'     => $user->id,
                'boutique_id' => $user->boutique_id ?? null,
            ]
        );

        return response()->json(['message' => 'Token enregistré']);
    }

    public function destroy(Request $request)
    {
        $request->validate(['token' => 'required|string']);
        FcmToken::where('token', $request->token)->delete();
        return response()->json(['message' => 'Token supprimé']);
    }
}
