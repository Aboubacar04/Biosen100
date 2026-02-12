<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Depense;
use Illuminate\Http\Request;

class DepenseController extends Controller
{
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $perPage = $request->input('per_page', 15);
        $query   = Depense::query();

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        return response()->json(
            $query->orderBy('date_depense', 'desc')->paginate($perPage)
        );
    }


    public function parDate(Request $request)
    {
        $request->validate(['date' => 'required|date']);

        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Depense::parDate($request->date);

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        return response()->json($query->get());
    }

    /** ➕ CRÉER */
    public function store(Request $request)
    {
        $user       = $request->user();
        $boutiqueId = $user->isAdmin() ? $request->boutique_id : $user->boutique_id;

        $request->validate([
            'description'  => 'required|string',
            'montant'      => 'required|numeric|min:0',
            'categorie'    => 'required|string|max:255',
            'date_depense' => 'required|date',
            'boutique_id'  => $user->isAdmin() ? 'required|exists:boutiques,id' : 'nullable',
        ]);

        $depense = Depense::create([
            'description'  => $request->description,
            'montant'      => $request->montant,
            'categorie'    => $request->categorie,
            'date_depense' => $request->date_depense,
            'boutique_id'  => $boutiqueId,
        ]);

        return response()->json(['message' => 'Dépense créée avec succès', 'depense' => $depense], 201);
    }

    public function show(Depense $depense)
    {
        $depense->load('boutique');
        return response()->json($depense);
    }

    /** ✏️ MODIFIER */
    public function update(Request $request, Depense $depense)
    {
        $user = $request->user();

        if (!$user->isAdmin() && (int) $depense->boutique_id !== (int) $user->boutique_id) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier une dépense d\'une autre boutique.'], 403);
        }

        $request->validate([
            'description'  => 'sometimes|string',
            'montant'      => 'sometimes|numeric|min:0',
            'categorie'    => 'sometimes|string|max:255',
            'date_depense' => 'sometimes|date',
        ]);

        $depense->update($request->only(['description', 'montant', 'categorie', 'date_depense']));

        return response()->json(['message' => 'Dépense modifiée avec succès', 'depense' => $depense]);
    }

    /** 🗑️ SUPPRIMER */
    public function destroy(Request $request, Depense $depense)
    {
        $user = $request->user();

        if (!$user->isAdmin() && (int) $depense->boutique_id !== (int) $user->boutique_id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer une dépense d\'une autre boutique.'], 403);
        }

        $depense->delete();

        return response()->json(['message' => 'Dépense supprimée avec succès']);
    }
}
