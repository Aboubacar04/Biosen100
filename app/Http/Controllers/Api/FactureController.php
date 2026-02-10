<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use Illuminate\Http\Request;

class FactureController extends Controller
{
    /**
     * 📋 LISTE toutes les factures
     * Route : GET /api/factures
     */
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $factures = Facture::whereHas('commande', function($query) use ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        })
            ->with('commande.client')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($factures);
    }

    /**
     * 👁️ AFFICHER une facture
     * Route : GET /api/factures/{id}
     */
    public function show(Facture $facture)
    {
        $facture->load([
            'commande.boutique',
            'commande.client',
            'commande.employe',
            'commande.produits'
        ]);

        return response()->json($facture);
    }

    /**
     * 🔍 RECHERCHER une facture par numéro
     * Route : GET /api/factures/search?numero=FAC-2026-00001
     */
    public function search(Request $request)
    {
        $request->validate([
            'numero' => 'required|string',
        ]);

        $facture = Facture::where('numero_facture', $request->numero)
            ->with('commande.client')
            ->first();

        if (!$facture) {
            return response()->json([
                'message' => 'Facture non trouvée'
            ], 404);
        }

        return response()->json($facture);
    }
}
