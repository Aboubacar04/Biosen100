<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeController extends Controller
{
    /**
     * Liste des employés avec recherche
     * GET /api/employes?boutique_id=&actif=&search=
     */
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $search = $request->input('search', '');
        $actif = $request->input('actif');

        $query = Employe::query();

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
     * Créer un employé
     * POST /api/employes
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $boutiqueId = $user->isAdmin() ? $request->boutique_id : $user->boutique_id;

        $request->validate([
            'nom'         => 'required|string|max:255',
            'telephone'   => 'required|string|max:20',
            'photo'       => 'nullable|image|max:2048',
            'boutique_id' => $user->isAdmin() ? 'required|exists:boutiques,id' : 'nullable',
        ]);

        $data = [
            'nom'         => $request->nom,
            'telephone'   => $request->telephone,
            'boutique_id' => $boutiqueId,
            'actif'       => true,
        ];

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('employes', 'public');
            $data['photo'] = $path;
        }

        $employe = Employe::create($data);

        return response()->json([
            'message' => 'Employé créé avec succès',
            'employe' => $employe,
        ], 201);
    }

    /**
     * Afficher un employé
     * GET /api/employes/{id}
     */
    public function show(Employe $employe)
    {
        return response()->json($employe);
    }

    /**
     * Modifier un employé
     * PUT /api/employes/{id}
     */
    public function update(Request $request, Employe $employe)
    {
        $user = $request->user();

        if (!$user->isAdmin() && (int) $employe->boutique_id !== (int) $user->boutique_id) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier un employé d\'une autre boutique.'], 403);
        }

        $request->validate([
            'nom'       => 'sometimes|string|max:255',
            'telephone' => 'sometimes|string|max:20',
            'actif'     => 'sometimes|boolean',
            'photo'     => 'nullable|image|max:2048',
        ]);

        if ($request->has('nom')) {
            $employe->nom = $request->nom;
        }

        if ($request->has('telephone')) {
            $employe->telephone = $request->telephone;
        }

        if ($request->has('actif')) {
            $employe->actif = $request->actif;
        }

        if ($request->hasFile('photo')) {
            // Supprimer ancienne photo
            if ($employe->photo) {
                Storage::disk('public')->delete($employe->photo);
            }
            $path = $request->file('photo')->store('employes', 'public');
            $employe->photo = $path;
        }

        $employe->save();

        return response()->json([
            'message' => 'Employé modifié avec succès',
            'employe' => $employe,
        ]);
    }

    /**
     * Supprimer un employé
     * DELETE /api/employes/{id}
     */
    public function destroy(Request $request, Employe $employe)
    {
        $user = $request->user();

        if (!$user->isAdmin() && (int) $employe->boutique_id !== (int) $user->boutique_id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer un employé d\'une autre boutique.'], 403);
        }

        // Supprimer la photo
        if ($employe->photo) {
            Storage::disk('public')->delete($employe->photo);
        }

        $employe->delete();

        return response()->json(['message' => 'Employé supprimé avec succès']);
    }
}
