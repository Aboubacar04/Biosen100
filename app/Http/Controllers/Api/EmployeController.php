<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employe;
use App\Services\ImageService;
use Illuminate\Http\Request;

class EmployeController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * 📋 LISTE tous les employés
     */
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Employe::query();

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $employes = $query->orderBy('nom')->get();

        return response()->json($employes);
    }

    /**
     * ➕ CRÉER un employé
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom' => 'required|string|max:255',
            'telephone' => 'required|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'boutique_id' => 'required|exists:boutiques,id',
        ]);

        $data = $request->only(['nom', 'telephone', 'boutique_id']);

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->imageService->upload($request->file('photo'), 'employes');
        }

        $employe = Employe::create($data);

        return response()->json([
            'message' => 'Employé créé avec succès',
            'employe' => $employe
        ], 201);
    }

    /**
     * 👁️ AFFICHER un employé
     */
    public function show(Employe $employe)
    {
        $employe->load('boutique', 'commandes');
        return response()->json($employe);
    }

    /**
     * ✏️ MODIFIER un employé
     */
    public function update(Request $request, Employe $employe)
    {
        $request->validate([
            'nom' => 'sometimes|string|max:255',
            'telephone' => 'sometimes|string|max:20',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'actif' => 'sometimes|boolean',
        ]);

        $data = $request->only(['nom', 'telephone', 'actif']);

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->imageService->update(
                $request->file('photo'),
                $employe->photo,
                'employes'
            );
        }

        $employe->update($data);

        return response()->json([
            'message' => 'Employé modifié avec succès',
            'employe' => $employe
        ]);
    }

    /**
     * 🗑️ SUPPRIMER un employé
     */
    public function destroy(Employe $employe)
    {
        $this->imageService->delete($employe->photo);
        $employe->delete();

        return response()->json([
            'message' => 'Employé supprimé avec succès'
        ]);
    }
}
