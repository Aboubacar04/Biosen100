<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Gamme;
use Illuminate\Http\Request;

class GammeController extends Controller
{
    // 📋 LISTE DES GAMMES
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Gamme::with(['produits', 'boutique']);

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        if ($request->input('actif') !== null) {
            $query->where('actif', $request->boolean('actif'));
        }

        return response()->json($query->orderBy('nom')->get());
    }

    // 👁️ AFFICHER UNE GAMME
    public function show(Gamme $gamme)
    {
        return response()->json($gamme->load(['produits', 'boutique']));
    }

    // ➕ CRÉER UNE GAMME
    public function store(Request $request)
    {
        $user = $request->user();

        $boutiqueId = $user->isAdmin()
            ? $request->boutique_id
            : $user->boutique_id;

        $request->validate([
            'nom'                  => 'required|string|max:255',
            'description'          => 'nullable|string',
            'boutique_id'          => $user->isAdmin() ? 'required|exists:boutiques,id' : 'nullable',
            'produits'             => 'required|array|min:1',
            'produits.*.produit_id' => 'required|exists:produits,id',
            'produits.*.quantite'  => 'required|integer|min:1',
        ]);

        $gamme = Gamme::create([
            'nom'         => $request->nom,
            'description' => $request->description,
            'boutique_id' => $boutiqueId,
            'actif'       => true,
        ]);

        foreach ($request->produits as $item) {
            $gamme->produits()->attach($item['produit_id'], [
                'quantite' => $item['quantite'],
            ]);
        }

        return response()->json([
            'message' => 'Gamme créée avec succès',
            'gamme'   => $gamme->load('produits'),
        ], 201);
    }

    // ✏️ MODIFIER UNE GAMME
    public function update(Request $request, Gamme $gamme)
    {
        $request->validate([
            'nom'                  => 'sometimes|string|max:255',
            'description'          => 'nullable|string',
            'actif'                => 'sometimes|boolean',
            'produits'             => 'sometimes|array|min:1',
            'produits.*.produit_id' => 'required|exists:produits,id',
            'produits.*.quantite'  => 'required|integer|min:1',
        ]);

        $gamme->update($request->only(['nom', 'description', 'actif']));

        if ($request->has('produits')) {
            $syncData = [];
            foreach ($request->produits as $item) {
                $syncData[$item['produit_id']] = ['quantite' => $item['quantite']];
            }
            $gamme->produits()->sync($syncData);
        }

        return response()->json([
            'message' => 'Gamme modifiée avec succès',
            'gamme'   => $gamme->load('produits'),
        ]);
    }

    // 🗑️ SUPPRIMER UNE GAMME
    public function destroy(Gamme $gamme)
    {
        $gamme->delete();

        return response()->json(['message' => 'Gamme supprimée avec succès']);
    }
}