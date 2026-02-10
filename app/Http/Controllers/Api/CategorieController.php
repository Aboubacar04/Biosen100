<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Categorie;
use Illuminate\Http\Request;

class CategorieController extends Controller
{
    /**
     * 📋 LISTE toutes les catégories
     */
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Categorie::withCount('produits');

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $categories = $query->orderBy('nom')->get();

        return response()->json($categories);
    }

    /**
     * ➕ CRÉER une catégorie
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'boutique_id' => 'required|exists:boutiques,id',
        ]);

        $categorie = Categorie::create($request->only(['nom', 'description', 'boutique_id']));

        return response()->json([
            'message' => 'Catégorie créée avec succès',
            'categorie' => $categorie
        ], 201);
    }

    /**
     * 👁️ AFFICHER une catégorie
     */
    public function show(Categorie $categorie)
    {
        $categorie->load('produits');
        return response()->json($categorie);
    }

    /**
     * ✏️ MODIFIER une catégorie
     */
    public function update(Request $request, Categorie $categorie)
    {
        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        $categorie->update($request->only(['nom', 'description']));

        return response()->json([
            'message' => 'Catégorie modifiée avec succès',
            'categorie' => $categorie
        ]);
    }

    /**
     * 🗑️ SUPPRIMER une catégorie
     */
    public function destroy(Categorie $categorie)
    {
        if ($categorie->produits()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer : cette catégorie contient des produits'
            ], 400);
        }

        $categorie->delete();

        return response()->json([
            'message' => 'Catégorie supprimée avec succès'
        ]);
    }
}
