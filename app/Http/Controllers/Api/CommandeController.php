<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Produit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommandeController extends Controller
{
    /**
     * 📋 LISTE toutes les commandes
     */
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Commande::with(['client', 'employe', 'livreur']);

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $commandes = $query->orderBy('created_at', 'desc')->get();

        return response()->json($commandes);
    }

    /**
     * 📝 LISTE des commandes EN COURS
     */
    public function enCours(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Commande::enCours()->with(['client', 'employe', 'livreur', 'produits']);

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $commandes = $query->orderBy('created_at', 'desc')->get();

        return response()->json($commandes);
    }

    /**
     * ✅ LISTE des commandes VALIDÉES
     */
    public function validees(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Commande::validees()->with(['client', 'employe', 'livreur', 'facture']);

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $commandes = $query->orderBy('date_validation', 'desc')->get();

        return response()->json($commandes);
    }

    /**
     * ❌ LISTE des commandes ANNULÉES
     */
    public function annulees(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Commande::annulees()->with(['client', 'employe', 'annuleePar']);

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $commandes = $query->orderBy('date_annulation', 'desc')->get();

        return response()->json($commandes);
    }

    /**
     * 📅 HISTORIQUE par date
     */
    public function historique(Request $request)
    {
        $request->validate(['date' => 'required|date']);

        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Commande::parDate($request->date)->with(['client', 'employe', 'livreur']);

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $commandes = $query->get();

        return response()->json($commandes);
    }

    /**
     * ➕ CRÉER une commande
     */
    public function store(Request $request)
    {
        $request->validate([
            'boutique_id' => 'required|exists:boutiques,id',
            'client_id' => 'nullable|exists:clients,id',
            'employe_id' => 'required|exists:employes,id',
            'livreur_id' => 'nullable|exists:livreurs,id',
            'type_commande' => 'required|in:sur_place,livraison',
            'notes' => 'nullable|string',
            'produits' => 'required|array|min:1',
            'produits.*.produit_id' => 'required|exists:produits,id',
            'produits.*.quantite' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $commande = Commande::create([
                'boutique_id' => $request->boutique_id,
                'client_id' => $request->client_id,
                'employe_id' => $request->employe_id,
                'livreur_id' => $request->livreur_id,
                'type_commande' => $request->type_commande,
                'notes' => $request->notes,
                'total' => 0,
            ]);

            $total = 0;

            foreach ($request->produits as $item) {
                $produit = Produit::findOrFail($item['produit_id']);

                $prixUnitaire = $produit->prix_vente;
                $quantite = $item['quantite'];
                $sousTotal = $prixUnitaire * $quantite;

                $commande->produits()->attach($produit->id, [
                    'quantite' => $quantite,
                    'prix_unitaire' => $prixUnitaire,
                    'sous_total' => $sousTotal,
                ]);

                $total += $sousTotal;
            }

            $commande->update(['total' => $total]);

            DB::commit();

            return response()->json([
                'message' => 'Commande créée avec succès',
                'commande' => $commande->load(['produits', 'client', 'employe', 'livreur'])
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 👁️ AFFICHER une commande
     */
    public function show(Commande $commande)
    {
        $commande->load([
            'boutique', 'client', 'employe', 'livreur',
            'produits', 'facture', 'annuleePar'
        ]);

        return response()->json($commande);
    }

    /**
     * ✅ VALIDER une commande
     */
    public function valider(Commande $commande)
    {
        if ($commande->statut !== 'en_cours') {
            return response()->json([
                'message' => 'Seules les commandes en cours peuvent être validées'
            ], 400);
        }

        DB::beginTransaction();

        try {
            $commande->valider();
            DB::commit();

            return response()->json([
                'message' => 'Commande validée avec succès',
                'commande' => $commande->load(['facture', 'produits'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la validation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ❌ ANNULER une commande
     */
    public function annuler(Request $request, Commande $commande)
    {
        $request->validate(['raison' => 'required|string']);

        DB::beginTransaction();

        try {
            $commande->annuler($request->raison, $request->user()->id);
            DB::commit();

            return response()->json([
                'message' => 'Commande annulée avec succès',
                'commande' => $commande->load('annuleePar')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de l\'annulation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✏️ MODIFIER une commande EN COURS
     */
    public function update(Request $request, Commande $commande)
    {
        if ($commande->statut !== 'en_cours') {
            return response()->json([
                'message' => 'Seules les commandes en cours peuvent être modifiées'
            ], 400);
        }

        $request->validate([
            'client_id' => 'nullable|exists:clients,id',
            'employe_id' => 'sometimes|exists:employes,id',
            'livreur_id' => 'nullable|exists:livreurs,id',
            'type_commande' => 'sometimes|in:sur_place,livraison',
            'notes' => 'nullable|string',
            'produits' => 'sometimes|array|min:1',
            'produits.*.produit_id' => 'required|exists:produits,id',
            'produits.*.quantite' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $commande->update($request->only([
                'client_id', 'employe_id', 'livreur_id', 'type_commande', 'notes'
            ]));

            if ($request->has('produits')) {
                $commande->produits()->detach();

                $total = 0;

                foreach ($request->produits as $item) {
                    $produit = Produit::findOrFail($item['produit_id']);

                    $prixUnitaire = $produit->prix_vente;
                    $quantite = $item['quantite'];
                    $sousTotal = $prixUnitaire * $quantite;

                    $commande->produits()->attach($produit->id, [
                        'quantite' => $quantite,
                        'prix_unitaire' => $prixUnitaire,
                        'sous_total' => $sousTotal,
                    ]);

                    $total += $sousTotal;
                }

                $commande->update(['total' => $total]);
            }

            DB::commit();

            return response()->json([
                'message' => 'Commande modifiée avec succès',
                'commande' => $commande->load(['produits', 'client', 'employe', 'livreur'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Erreur lors de la modification',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🗑️ SUPPRIMER une commande EN COURS
     */
    public function destroy(Commande $commande)
    {
        if ($commande->statut !== 'en_cours') {
            return response()->json([
                'message' => 'Seules les commandes en cours peuvent être supprimées'
            ], 400);
        }

        $commande->delete();

        return response()->json([
            'message' => 'Commande supprimée avec succès'
        ]);
    }
}
