<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Boutique;
use App\Services\ImageService;
use Illuminate\Http\Request;

class BoutiqueController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * 📋 LISTE toutes les boutiques
     * Route : GET /api/boutiques
     * Permission : Admin uniquement
     */
    public function index()
    {
        $boutiques = Boutique::withCount(['produits', 'commandes', 'employes'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($boutiques);
    }

    /**
     * ➕ CRÉER une boutique
     * Route : POST /api/boutiques
     * Permission : Admin uniquement
     */
    public function store(Request $request)
    {
        // Validation
        $request->validate([
            'nom' => 'required|string|max:255',
            'adresse' => 'required|string',
            'telephone' => 'required|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Récupérer les données
        $data = $request->only(['nom', 'adresse', 'telephone']);

        // Upload du logo si présent
        if ($request->hasFile('logo')) {
            $data['logo'] = $this->imageService->upload($request->file('logo'), 'logos');
        }

        // Créer la boutique
        $boutique = Boutique::create($data);

        return response()->json([
            'message' => 'Boutique créée avec succès',
            'boutique' => $boutique
        ], 201);
    }

    /**
     * 👁️ AFFICHER une boutique
     * Route : GET /api/boutiques/{id}
     */
    public function show(Boutique $boutique)
    {
        // Charger les relations SANS les clients (on va les paginer)
        $boutique->load(['produits', 'employes', 'livreurs']);

        // Ajouter les clients paginés (15 par page par défaut)
        $perPage = request()->get('per_page', 15);
        $boutique->clients_paginated = $boutique->clients()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($boutique);
    }

    /**
     * ✏️ MODIFIER une boutique
     * Route : PUT/PATCH /api/boutiques/{id}
     * Permission : Admin uniquement
     */
    public function update(Request $request, Boutique $boutique)
    {
        // Validation
        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'adresse' => 'sometimes|string',
            'telephone' => 'sometimes|string|max:20',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'actif' => 'sometimes|boolean',
        ]);

        // Récupérer les données
        $data = $request->only(['nom', 'adresse', 'telephone', 'actif']);

        // Gérer le logo
        if ($request->hasFile('logo')) {
            // Supprimer l'ancien logo et uploader le nouveau
            $data['logo'] = $this->imageService->update(
                $request->file('logo'),
                $boutique->logo,
                'logos'
            );
        }

        // Mettre à jour
        $boutique->update($data);

        return response()->json([
            'message' => 'Boutique modifiée avec succès',
            'boutique' => $boutique
        ]);
    }

    /**
     * 🗑️ SUPPRIMER une boutique
     * Route : DELETE /api/boutiques/{id}
     * Permission : Admin uniquement
     */
    public function destroy(Boutique $boutique)
    {
        // Supprimer le logo
        $this->imageService->delete($boutique->logo);

        // Supprimer la boutique (cascade supprime automatiquement le reste)
        $boutique->delete();

        return response()->json([
            'message' => 'Boutique supprimée avec succès'
        ]);
    }

    /**
     * 🔄 ACTIVER/DÉSACTIVER une boutique
     * Route : POST /api/boutiques/{id}/toggle-status
     * Permission : Admin uniquement
     */
    public function toggleStatus(Boutique $boutique)
    {
        $boutique->update(['actif' => !$boutique->actif]);

        return response()->json([
            'message' => 'Statut modifié avec succès',
            'boutique' => $boutique
        ]);
    }
}