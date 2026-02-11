<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use Illuminate\Http\Request;

class FactureController extends Controller
{
    /**
     * 📋 LISTE toutes les factures (PAGINÉE)
     * Route : GET /api/factures?page=1&per_page=15
     */
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $perPage = $request->input('per_page', 15);

        $query = Facture::with(['commande.client', 'commande.employe']);

        if ($boutiqueId) {
            $query->whereHas('commande', function($q) use ($boutiqueId) {
                $q->where('boutique_id', $boutiqueId);
            });
        }

        // Filtre par date
        if ($request->input('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        // Filtre par mois
        if ($request->input('mois') && $request->input('annee')) {
            $query->whereMonth('created_at', $request->input('mois'))
                ->whereYear('created_at', $request->input('annee'));
        }

        $factures = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($factures);
    }

    /**
     * 👁️ AFFICHER une facture
     * Route : GET /api/factures/{id}
     */
    public function show(Facture $facture)
    {
        $facture->load([
            'commande.client',
            'commande.employe',
            'commande.livreur',
            'commande.produits',
            'commande.boutique'
        ]);

        return response()->json($facture);
    }

    /**
     * 👁️ AFFICHER la facture d'une commande
     * Route : GET /api/commandes/{id}/facture
     */
    public function parCommande($commandeId)
    {
        $facture = Facture::where('commande_id', $commandeId)
            ->with([
                'commande.client',
                'commande.employe',
                'commande.livreur',
                'commande.produits',
                'commande.boutique'
            ])
            ->firstOrFail();

        return response()->json($facture);
    }
}
