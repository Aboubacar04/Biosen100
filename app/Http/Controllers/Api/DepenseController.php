<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Depense;
use Illuminate\Http\Request;

class DepenseController extends Controller
{
    /**
     * 📋 LISTE toutes les dépenses
     */
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Depense::query();

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $depenses = $query->orderBy('date_depense', 'desc')->get();

        return response()->json($depenses);
    }

    /**
     * 📅 DÉPENSES par date
     */
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

        $depenses = $query->get();

        return response()->json($depenses);
    }

    /**
     * ➕ CRÉER une dépense
     */
    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string',
            'montant' => 'required|numeric|min:0',
            'categorie' => 'required|string|max:255',
            'date_depense' => 'required|date',
            'boutique_id' => 'required|exists:boutiques,id',
        ]);

        $depense = Depense::create($request->only([
            'description', 'montant', 'categorie', 'date_depense', 'boutique_id'
        ]));

        return response()->json([
            'message' => 'Dépense créée avec succès',
            'depense' => $depense
        ], 201);
    }

    /**
     * 👁️ AFFICHER une dépense
     */
    public function show(Depense $depense)
    {
        $depense->load('boutique');
        return response()->json($depense);
    }

    /**
     * ✏️ MODIFIER une dépense
     */
    public function update(Request $request, Depense $depense)
    {
        $request->validate([
            'description' => 'sometimes|string',
            'montant' => 'sometimes|numeric|min:0',
            'categorie' => 'sometimes|string|max:255',
            'date_depense' => 'sometimes|date',
        ]);

        $depense->update($request->only([
            'description', 'montant', 'categorie', 'date_depense'
        ]));

        return response()->json([
            'message' => 'Dépense modifiée avec succès',
            'depense' => $depense
        ]);
    }

    /**
     * 🗑️ SUPPRIMER une dépense
     */
    public function destroy(Depense $depense)
    {
        $depense->delete();

        return response()->json([
            'message' => 'Dépense supprimée avec succès'
        ]);
    }
}
