<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Facture;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FactureController extends Controller
{
    // ─────────────────────────────────────────────────────────────
    // 📋 LISTE TOUTES LES FACTURES
    // GET /api/factures
    // ─────────────────────────────────────────────────────────────
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

        if ($request->input('date')) {
            $query->whereDate('date_facture', $request->input('date'));
        }

        if ($request->input('mois') && $request->input('annee')) {
            $query->whereMonth('date_facture', $request->input('mois'))
                  ->whereYear('date_facture', $request->input('annee'));
        }

        return response()->json($query->orderBy('date_facture', 'desc')->paginate($perPage));
    }

    // ─────────────────────────────────────────────────────────────
    // 📅 FACTURES DU JOUR
    // GET /api/factures/aujourdhui
    // ─────────────────────────────────────────────────────────────
    public function aujourdhui(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $perPage = $request->input('per_page', 15);
        $query = Facture::with(['commande.client', 'commande.employe'])
            ->whereDate('date_facture', Carbon::today());

        if ($boutiqueId) {
            $query->whereHas('commande', function($q) use ($boutiqueId) {
                $q->where('boutique_id', $boutiqueId);
            });
        }

        $totalFactures = (clone $query)->count();
        $montantTotal = (clone $query)->sum('montant_total');

        $factures = $query->orderBy('date_facture', 'desc')->paginate($perPage);

        return response()->json([
            'resume' => [
                'date' => Carbon::today()->format('Y-m-d'),
                'total_factures' => $totalFactures,
                'montant_total' => $montantTotal,
            ],
            'factures' => $factures,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // 📅 FACTURES DE LA SEMAINE
    // GET /api/factures/semaine
    // ─────────────────────────────────────────────────────────────
    public function semaine(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $perPage = $request->input('per_page', 15);
        $debutSemaine = Carbon::now()->startOfWeek();
        $finSemaine = Carbon::now()->endOfWeek();

        $query = Facture::with(['commande.client', 'commande.employe'])
            ->whereBetween('date_facture', [$debutSemaine, $finSemaine]);

        if ($boutiqueId) {
            $query->whereHas('commande', function($q) use ($boutiqueId) {
                $q->where('boutique_id', $boutiqueId);
            });
        }

        $totalFactures = (clone $query)->count();
        $montantTotal = (clone $query)->sum('montant_total');

        $factures = $query->orderBy('date_facture', 'desc')->paginate($perPage);

        return response()->json([
            'resume' => [
                'debut_semaine' => $debutSemaine->format('Y-m-d'),
                'fin_semaine' => $finSemaine->format('Y-m-d'),
                'total_factures' => $totalFactures,
                'montant_total' => $montantTotal,
            ],
            'factures' => $factures,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // 📅 FACTURES DU MOIS
    // GET /api/factures/mois?mois=2&annee=2026
    // ─────────────────────────────────────────────────────────────
    public function mois(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $perPage = $request->input('per_page', 15);
        $mois = $request->input('mois', Carbon::now()->month);
        $annee = $request->input('annee', Carbon::now()->year);

        $query = Facture::with(['commande.client', 'commande.employe'])
            ->whereMonth('date_facture', $mois)
            ->whereYear('date_facture', $annee);

        if ($boutiqueId) {
            $query->whereHas('commande', function($q) use ($boutiqueId) {
                $q->where('boutique_id', $boutiqueId);
            });
        }

        $totalFactures = (clone $query)->count();
        $montantTotal = (clone $query)->sum('montant_total');

        $factures = $query->orderBy('date_facture', 'desc')->paginate($perPage);

        return response()->json([
            'resume' => [
                'mois' => $mois,
                'annee' => $annee,
                'total_factures' => $totalFactures,
                'montant_total' => $montantTotal,
            ],
            'factures' => $factures,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // 📅 FACTURES DE L'ANNÉE
    // GET /api/factures/annee?annee=2026
    // ─────────────────────────────────────────────────────────────
    public function annee(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $perPage = $request->input('per_page', 15);
        $annee = $request->input('annee', Carbon::now()->year);

        $query = Facture::with(['commande.client', 'commande.employe'])
            ->whereYear('date_facture', $annee);

        if ($boutiqueId) {
            $query->whereHas('commande', function($q) use ($boutiqueId) {
                $q->where('boutique_id', $boutiqueId);
            });
        }

        $totalFactures = (clone $query)->count();
        $montantTotal = (clone $query)->sum('montant_total');

        // Statistiques par mois
        $parMoisQuery = Facture::whereYear('date_facture', $annee);
        
        if ($boutiqueId) {
            $parMoisQuery->whereHas('commande', function($q) use ($boutiqueId) {
                $q->where('boutique_id', $boutiqueId);
            });
        }

        $parMois = $parMoisQuery->selectRaw('MONTH(date_facture) as mois, COUNT(*) as total, SUM(montant_total) as montant')
            ->groupBy('mois')
            ->orderBy('mois')
            ->get();

        $factures = $query->orderBy('date_facture', 'desc')->paginate($perPage);

        return response()->json([
            'resume' => [
                'annee' => $annee,
                'total_factures' => $totalFactures,
                'montant_total' => $montantTotal,
                'par_mois' => $parMois,
            ],
            'factures' => $factures,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // 🔍 RECHERCHER UNE FACTURE PAR NUMÉRO
    // GET /api/factures/search?search=FAC-2026-001
    // ─────────────────────────────────────────────────────────────
    public function search(Request $request)
    {
        $search = $request->input('search', '');
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Facture::with(['commande.client', 'commande.employe']);

        if ($boutiqueId) {
            $query->whereHas('commande', function($q) use ($boutiqueId) {
                $q->where('boutique_id', $boutiqueId);
            });
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_facture', 'like', '%' . $search . '%')
                  ->orWhereHas('commande.client', function ($clientQuery) use ($search) {
                      $clientQuery->where('nom_complet', 'like', '%' . $search . '%')
                                  ->orWhere('telephone', 'like', '%' . $search . '%');
                  });
            });
        }

        return response()->json($query->orderBy('date_facture', 'desc')->paginate(15));
    }

    // ─────────────────────────────────────────────────────────────
    // 👁️ AFFICHER UNE FACTURE
    // GET /api/factures/{facture}
    // ─────────────────────────────────────────────────────────────
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

    // ─────────────────────────────────────────────────────────────
    // 👁️ AFFICHER LA FACTURE D'UNE COMMANDE
    // GET /api/commandes/{id}/facture
    // ─────────────────────────────────────────────────────────────
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