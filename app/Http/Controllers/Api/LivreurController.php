<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Livreur;
use Illuminate\Http\Request;

class LivreurController extends Controller
{
    /**
     * Liste des livreurs avec recherche
     * GET /api/livreurs?boutique_id=&actif=&search=
     */
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $search = $request->input('search', '');
        $actif = $request->input('actif');

        $query = Livreur::query();

        // Filtre boutique
        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        // Filtre actif
        if ($actif !== null) {
            $query->where('actif', (bool) $actif);
        }

        // 🔍 RECHERCHE par nom OU téléphone
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', '%' . $search . '%')
                    ->orWhere('telephone', 'like', '%' . $search . '%');
            });
        }

        return response()->json($query->get());
    }

    /**
     * Livreurs disponibles
     * GET /api/livreurs/disponibles?boutique_id=
     */
    public function disponibles(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Livreur::where('disponible', true)->where('actif', true);

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        return response()->json($query->get());
    }

    /**
     * Créer un livreur
     * POST /api/livreurs
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $boutiqueId = $user->isAdmin() ? $request->boutique_id : $user->boutique_id;

        $request->validate([
            'nom'         => 'required|string|max:255',
            'telephone'   => 'required|string|max:20',
            'boutique_id' => $user->isAdmin() ? 'required|exists:boutiques,id' : 'nullable',
        ]);

        $livreur = Livreur::create([
            'nom'         => $request->nom,
            'telephone'   => $request->telephone,
            'boutique_id' => $boutiqueId,
            'disponible'  => true,
            'actif'       => true,
        ]);

        return response()->json([
            'message' => 'Livreur créé avec succès',
            'livreur' => $livreur,
        ], 201);
    }

    /**
     * Afficher un livreur
     * GET /api/livreurs/{id}
     */
    public function show(Livreur $livreur)
    {
        return response()->json($livreur);
    }

    /**
     * Modifier un livreur
     * PUT /api/livreurs/{id}
     */
    public function update(Request $request, Livreur $livreur)
    {
        $user = $request->user();

        if (!$user->isAdmin() && (int) $livreur->boutique_id !== (int) $user->boutique_id) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier un livreur d\'une autre boutique.'], 403);
        }

        $request->validate([
            'nom'        => 'sometimes|string|max:255',
            'telephone'  => 'sometimes|string|max:20',
            'disponible' => 'sometimes|boolean',
            'actif'      => 'sometimes|boolean',
        ]);

        $livreur->update($request->only(['nom', 'telephone', 'disponible', 'actif']));

        return response()->json([
            'message' => 'Livreur modifié avec succès',
            'livreur' => $livreur,
        ]);
    }

    /**
     * Supprimer un livreur
     * DELETE /api/livreurs/{id}
     */
    public function destroy(Request $request, Livreur $livreur)
    {
        $user = $request->user();

        if (!$user->isAdmin() && (int) $livreur->boutique_id !== (int) $user->boutique_id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer un livreur d\'une autre boutique.'], 403);
        }

        $livreur->delete();

        return response()->json(['message' => 'Livreur supprimé avec succès']);
    }

    /**
     * Toggle disponibilité
     * POST /api/livreurs/{id}/toggle-disponibilite
     */
    public function toggleDisponibilite(Livreur $livreur)
    {
        $livreur->disponible = !$livreur->disponible;
        $livreur->save();

        return response()->json([
            'message' => 'Disponibilité mise à jour',
            'livreur' => $livreur,
        ]);
    }

    /**
     * Activer un livreur
     * POST /api/livreurs/{id}/activer
     */
    public function activer(Livreur $livreur)
    {
        $livreur->actif = true;
        $livreur->save();

        return response()->json([
            'message' => 'Livreur activé',
            'livreur' => $livreur,
        ]);
    }

    /**
     * Désactiver un livreur
     * POST /api/livreurs/{id}/desactiver
     */
    public function desactiver(Livreur $livreur)
    {
        $livreur->actif = false;
        $livreur->save();

        return response()->json([
            'message' => 'Livreur désactivé',
            'livreur' => $livreur,
        ]);
    }
}
