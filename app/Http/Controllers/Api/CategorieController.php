<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use Illuminate\Http\Request;

class CategorieController extends Controller
{
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Categorie::withCount('produits');

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        return response()->json($query->orderBy('nom')->get());
    }

    /** ➕ CRÉER */
    public function store(Request $request)
    {
        $user       = $request->user();
        $boutiqueId = $user->isAdmin() ? $request->boutique_id : $user->boutique_id;

        $request->validate([
            'nom'         => 'required|string|max:255',
            'description' => 'nullable|string',
            'boutique_id' => $user->isAdmin() ? 'required|exists:boutiques,id' : 'nullable',
        ]);

        $categorie = Categorie::create([
            'nom'         => $request->nom,
            'description' => $request->description,
            'boutique_id' => $boutiqueId,
        ]);

        return response()->json(['message' => 'Catégorie créée avec succès', 'categorie' => $categorie], 201);
    }

    public function show(Categorie $categorie)
    {
        $categorie->load('produits');
        return response()->json($categorie);
    }

    /** ✏️ MODIFIER */
    public function update(Request $request, Categorie $categorie)
    {
        $user = $request->user();

        if (!$user->isAdmin() && (int) $categorie->boutique_id !== (int) $user->boutique_id) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier une catégorie d\'une autre boutique.'], 403);
        }

        $request->validate([
            'nom'         => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $categorie->update($request->only(['nom', 'description']));

        return response()->json(['message' => 'Catégorie modifiée avec succès', 'categorie' => $categorie]);
    }

    /** 🗑️ SUPPRIMER */
    public function destroy(Request $request, Categorie $categorie)
    {
        $user = $request->user();

        if (!$user->isAdmin() && (int) $categorie->boutique_id !== (int) $user->boutique_id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer une catégorie d\'une autre boutique.'], 403);
        }

        if ($categorie->produits()->count() > 0) {
            return response()->json(['message' => 'Impossible de supprimer : cette catégorie contient des produits'], 400);
        }

        $categorie->delete();

        return response()->json(['message' => 'Catégorie supprimée avec succès']);
    }
}
