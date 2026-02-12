<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProduitController extends Controller
{
    /**
     * Liste des produits avec recherche et pagination
     * GET /api/produits?boutique_id=&categorie_id=&actif=&search=&per_page=&page=
     */
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $search = $request->input('search', '');
        $categorieId = $request->input('categorie_id');
        $actif = $request->input('actif');
        $perPage = $request->input('per_page', 15);

        $query = Produit::with('categorie');

        // Filtre boutique
        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        // Filtre catégorie
        if ($categorieId) {
            $query->where('categorie_id', $categorieId);
        }

        // Filtre actif
        if ($actif !== null) {
            $query->where('actif', (bool) $actif);
        }

        // 🔍 RECHERCHE par nom
        if ($search) {
            $query->where('nom', 'like', '%' . $search . '%');
        }

        return response()->json($query->paginate($perPage));
    }

    /**
     * Produits en stock faible
     * GET /api/produits/stock-faible?boutique_id=
     */
    public function stockFaible(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Produit::with('categorie')
            ->where('actif', true)
            ->whereColumn('stock', '<=', 'seuil_alerte');

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        return response()->json($query->get());
    }

    /**
     * Créer un produit
     * POST /api/produits
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $boutiqueId = $user->isAdmin() ? $request->boutique_id : $user->boutique_id;

        $request->validate([
            'nom'          => 'required|string|max:255',
            'description'  => 'nullable|string',
            'prix_vente'   => 'required|numeric|min:0',
            'stock'        => 'required|integer|min:0',
            'seuil_alerte' => 'required|integer|min:0',
            'categorie_id' => 'required|exists:categories,id',
            'image'        => 'nullable|image|max:2048',
            'boutique_id'  => $user->isAdmin() ? 'required|exists:boutiques,id' : 'nullable',
        ]);

        $data = [
            'nom'          => $request->nom,
            'description'  => $request->description,
            'prix_vente'   => $request->prix_vente,
            'stock'        => $request->stock,
            'seuil_alerte' => $request->seuil_alerte,
            'categorie_id' => $request->categorie_id,
            'boutique_id'  => $boutiqueId,
            'actif'        => true,
        ];

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('produits', 'public');
            $data['image'] = $path;
        }

        $produit = Produit::create($data);

        return response()->json([
            'message' => 'Produit créé avec succès',
            'produit' => $produit->load('categorie'),
        ], 201);
    }

    /**
     * Afficher un produit
     * GET /api/produits/{id}
     */
    public function show(Produit $produit)
    {
        return response()->json($produit->load('categorie'));
    }

    /**
     * Modifier un produit
     * PUT /api/produits/{id}
     */
    public function update(Request $request, Produit $produit)
    {
        $user = $request->user();

        if (!$user->isAdmin() && (int) $produit->boutique_id !== (int) $user->boutique_id) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier un produit d\'une autre boutique.'], 403);
        }

        $request->validate([
            'nom'          => 'sometimes|string|max:255',
            'description'  => 'nullable|string',
            'prix_vente'   => 'sometimes|numeric|min:0',
            'stock'        => 'sometimes|integer|min:0',
            'seuil_alerte' => 'sometimes|integer|min:0',
            'categorie_id' => 'sometimes|exists:categories,id',
            'actif'        => 'sometimes|boolean',
            'image'        => 'nullable|image|max:2048',
        ]);

        if ($request->has('nom')) {
            $produit->nom = $request->nom;
        }

        if ($request->has('description')) {
            $produit->description = $request->description;
        }

        if ($request->has('prix_vente')) {
            $produit->prix_vente = $request->prix_vente;
        }

        if ($request->has('stock')) {
            $produit->stock = $request->stock;
        }

        if ($request->has('seuil_alerte')) {
            $produit->seuil_alerte = $request->seuil_alerte;
        }

        if ($request->has('categorie_id')) {
            $produit->categorie_id = $request->categorie_id;
        }

        if ($request->has('actif')) {
            $produit->actif = $request->actif;
        }

        if ($request->hasFile('image')) {
            // Supprimer ancienne image
            if ($produit->image) {
                Storage::disk('public')->delete($produit->image);
            }
            $path = $request->file('image')->store('produits', 'public');
            $produit->image = $path;
        }

        $produit->save();

        return response()->json([
            'message' => 'Produit modifié avec succès',
            'produit' => $produit->load('categorie'),
        ]);
    }

    /**
     * Supprimer un produit
     * DELETE /api/produits/{id}
     */
    public function destroy(Request $request, Produit $produit)
    {
        $user = $request->user();

        if (!$user->isAdmin() && (int) $produit->boutique_id !== (int) $user->boutique_id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer un produit d\'une autre boutique.'], 403);
        }

        // Supprimer l'image
        if ($produit->image) {
            Storage::disk('public')->delete($produit->image);
        }

        $produit->delete();

        return response()->json(['message' => 'Produit supprimé avec succès']);
    }
}
