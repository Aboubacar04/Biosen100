<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Livreur;
use Illuminate\Http\Request;

class LivreurController extends Controller
{
    /**
     * 📋 LISTE tous les livreurs
     */
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Livreur::query();

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $livreurs = $query->orderBy('nom')->get();

        return response()->json($livreurs);
    }

    /**
     * ✅ LISTE des livreurs DISPONIBLES
     */
    public function disponibles(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Livreur::disponibles();

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $livreurs = $query->get();

        return response()->json($livreurs);
    }

    /**
     * ➕ CRÉER un livreur
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'telephone' => 'required|string|max:20',
            'boutique_id' => 'required|exists:boutiques,id',
        ]);

        $livreur = Livreur::create($request->only(['nom', 'telephone', 'boutique_id']));

        return response()->json([
            'message' => 'Livreur créé avec succès',
            'livreur' => $livreur
        ], 201);
    }

    /**
     * 👁️ AFFICHER un livreur
     */
    public function show(Livreur $livreur)
    {
        $livreur->load('boutique', 'commandes');
        return response()->json($livreur);
    }

    /**
     * ✏️ MODIFIER un livreur
     */
    public function update(Request $request, Livreur $livreur)
    {
        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'telephone' => 'sometimes|string|max:20',
            'disponible' => 'sometimes|boolean',
            'actif' => 'sometimes|boolean',
        ]);

        $livreur->update($request->only(['nom', 'telephone', 'disponible', 'actif']));

        return response()->json([
            'message' => 'Livreur modifié avec succès',
            'livreur' => $livreur
        ]);
    }

    /**
     * 🗑️ SUPPRIMER un livreur
     */
    public function destroy(Livreur $livreur)
    {
        $livreur->delete();

        return response()->json([
            'message' => 'Livreur supprimé avec succès'
        ]);
    }

    /**
     * 🔄 CHANGER la disponibilité
     */
    public function toggleDisponibilite(Livreur $livreur)
    {
        $livreur->update(['disponible' => !$livreur->disponible]);

        return response()->json([
            'message' => 'Disponibilité modifiée',
            'livreur' => $livreur
        ]);
    }
}
