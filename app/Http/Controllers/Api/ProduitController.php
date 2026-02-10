<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Produit;
use App\Services\ImageService;
use Illuminate\Http\Request;

class ProduitController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * 📋 LISTE tous les produits
     */
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Produit::with('categorie');

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $produits = $query->orderBy('created_at', 'desc')->get();

        return response()->json($produits);
    }

    /**
     * ⚠️ PRODUITS avec stock faible
     */
    public function stockFaible(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Produit::stockFaible()->with('categorie');

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $produits = $query->get();

        return response()->json($produits);
    }

    /**
     * ➕ CRÉER un produit
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'prix_vente' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'seuil_alerte' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'categorie_id' => 'required|exists:categories,id',
            'boutique_id' => 'required|exists:boutiques,id',
        ]);

        $data = $request->only([
            'nom', 'description', 'prix_vente', 'stock',
            'seuil_alerte', 'categorie_id', 'boutique_id'
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $this->imageService->upload($request->file('image'), 'produits');
        }

        $produit = Produit::create($data);

        return response()->json([
            'message' => 'Produit créé avec succès',
            'produit' => $produit->load('categorie')
        ], 201);
    }

    /**
     * 👁️ AFFICHER un produit
     */
    public function show(Produit $produit)
    {
        $produit->load('categorie', 'boutique');
        return response()->json($produit);
    }

    /**
     * ✏️ MODIFIER un produit
     */
    public function update(Request $request, Produit $produit)
    {
        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'prix_vente' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'seuil_alerte' => 'nullable|integer|min:0',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'categorie_id' => 'sometimes|exists:categories,id',
            'actif' => 'sometimes|boolean',
        ]);

        $data = $request->only([
            'nom', 'description', 'prix_vente', 'stock',
            'seuil_alerte', 'categorie_id', 'actif'
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $this->imageService->update(
                $request->file('image'),
                $produit->image,
                'produits'
            );
        }

        $produit->update($data);

        return response()->json([
            'message' => 'Produit modifié avec succès',
            'produit' => $produit->load('categorie')
        ]);
    }

    /**
     * 🗑️ SUPPRIMER un produit
     */
    public function destroy(Produit $produit)
    {
        $this->imageService->delete($produit->image);
        $produit->delete();

        return response()->json([
            'message' => 'Produit supprimé avec succès'
        ]);
    }
}
