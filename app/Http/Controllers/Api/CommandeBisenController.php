<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommandeBisen;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommandeBisenController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // 📋 LISTE TOUTES LES COMMANDES (AVEC FILTRAGE)
    // ─────────────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $query = CommandeBisen::with('saisisseur');

        // 📅 Filtrer par DATE SPÉCIFIQUE
        if ($request->input('date')) {
            $query->whereDate('created_at', $request->input('date'));
        }

        // 📆 Filtrer par SEMAINE
        if ($request->input('semaine')) {
            $date = Carbon::parse($request->input('semaine'));
            $query->whereBetween('created_at', [
                $date->copy()->startOfWeek(),
                $date->copy()->endOfWeek(),
            ]);
        }

        // 📅 Filtrer par MOIS ET ANNÉE
        if ($request->input('mois') && $request->input('annee')) {
            $query->whereMonth('created_at', $request->input('mois'))
                  ->whereYear('created_at', $request->input('annee'));
        }

        // 📅 Filtrer par ANNÉE UNIQUEMENT
        if ($request->input('annee') && !$request->input('mois')) {
            $query->whereYear('created_at', $request->input('annee'));
        }

        // 🔍 RECHERCHE PAR TÉLÉPHONE OU NOM
        if ($request->input('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('telephone', 'like', '%' . $search . '%')
                  ->orWhere('nom_client', 'like', '%' . $search . '%');
            });
        }

        // 👤 Filtrer par SAISISSEUR
        if ($request->input('saisisseur_id')) {
            $query->where('saisie_par', $request->input('saisisseur_id'));
        }

        // Calculer les stats AVANT pagination
        $totalCommandes = (clone $query)->count();
        $totalTelephones = (clone $query)->count();

        // Récupérer les commandes avec pagination
        $commandes = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'resume' => [
                'total_commandes' => $totalCommandes,
                'total_saisies'   => $totalTelephones,
                'date_filtre'     => $request->input('date'),
            ],
            'commandes' => $commandes,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 📅 HISTORIQUE PAR DATE (DÉTAILLÉ)
    // ─────────────────────────────────────────────────────────────────────────
    public function historique(Request $request)
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        $perPage = $request->input('per_page', 20);

        $query = CommandeBisen::with('saisisseur')
            ->whereDate('created_at', $request->date);

        // Stats pour cette date
        $totalCommandes = (clone $query)->count();
        $commandesParSaisisseur = (clone $query)
            ->select('saisie_par', DB::raw('count(*) as total'))
            ->groupBy('saisie_par')
            ->with('saisisseur')
            ->get();

        $commandes = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'resume' => [
                'date'            => $request->date,
                'total_commandes' => $totalCommandes,
                'par_saisisseur'  => $commandesParSaisisseur,
            ],
            'commandes' => $commandes,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ➕ CRÉER UNE COMMANDE (RESPONSABLE DE SAISIE)
    // ─────────────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        // VALIDATION: Seul téléphone, commercial et produits sont obligatoires
        $validated = $request->validate([
            'telephone'  => 'required|string|min:7',
            'nom_client' => 'nullable|string|max:255',
            'adresse'    => 'nullable|string|max:500',
            'commercial' => 'required|string|max:255',
            'produits'   => 'required|string|min:5',
        ], [
            'telephone.required'  => 'Le numéro de téléphone est obligatoire',
            'telephone.min'       => 'Le numéro doit contenir au moins 7 caractères',
            'commercial.required' => 'Le nom du commercial est obligatoire',
            'produits.required'   => 'Les produits sont obligatoires',
            'produits.min'        => 'Décrivez au moins 5 caractères pour les produits',
        ]);

        try {
            // Créer la commande
            $commande = CommandeBisen::create([
                'telephone'  => $validated['telephone'],
                'nom_client' => $validated['nom_client'] ?? 'Client',
                'adresse'    => $validated['adresse'],
                'commercial' => $validated['commercial'],
                'produits'   => $validated['produits'],
                'saisie_par' => auth()->id(),
            ]);

            // Charger la relation saisisseur
            $commande->load('saisisseur');

            return response()->json([
                'message'  => 'Commande saisie et envoyée avec succès',
                'commande' => $commande,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la création de la commande',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 👁️ AFFICHER UNE COMMANDE
    // ─────────────────────────────────────────────────────────────────────────
    public function show(CommandeBisen $commandeBisen)
    {
        return response()->json(
            $commandeBisen->load('saisisseur')
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ✏️ MODIFIER UNE COMMANDE
    // ─────────────────────────────────────────────────────────────────────────
    public function update(Request $request, CommandeBisen $commandeBisen)
    {
        $validated = $request->validate([
            'telephone'  => 'sometimes|string|min:7',
            'nom_client' => 'nullable|string|max:255',
            'adresse'    => 'nullable|string|max:500',
            'commercial' => 'sometimes|string|max:255',
            'produits'   => 'sometimes|string|min:5',
        ]);

        try {
            $commandeBisen->update($validated);
            $commandeBisen->load('saisisseur');

            return response()->json([
                'message'  => 'Commande modifiée avec succès',
                'commande' => $commandeBisen,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la modification',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 🗑️ SUPPRIMER UNE COMMANDE
    // ─────────────────────────────────────────────────────────────────────────
    public function destroy(CommandeBisen $commandeBisen)
    {
        $nom_client = $commandeBisen->nom_client;
        
        try {
            $commandeBisen->delete();

            return response()->json([
                'message' => "Commande de {$nom_client} supprimée avec succès"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 🔍 RECHERCHE COMMANDES
    // ─────────────────────────────────────────────────────────────────────────
    public function search(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 20);

        $query = CommandeBisen::with('saisisseur');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('telephone', 'like', '%' . $search . '%')
                  ->orWhere('nom_client', 'like', '%' . $search . '%')
                  ->orWhere('commercial', 'like', '%' . $search . '%');
            });
        }

        return response()->json(
            $query->orderBy('created_at', 'desc')->paginate($perPage)
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 📊 STATISTIQUES GÉNÉRALES
    // ─────────────────────────────────────────────────────────────────────────
    public function statistiques(Request $request)
    {
        $perPage = $request->input('per_page', 20);

        // Statistiques totales
        $totalCommandes = CommandeBisen::count();
        $commandesAujourdhui = CommandeBisen::whereDate('created_at', Carbon::today())->count();
        $commandesCettemois = CommandeBisen::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->count();

        // Commandes par saisisseur
        $parSaisisseur = CommandeBisen::select('saisie_par', DB::raw('count(*) as total'))
            ->groupBy('saisie_par')
            ->with('saisisseur')
            ->orderBy('total', 'desc')
            ->get();

        // Commandes par jour (7 derniers jours)
        $parJour = CommandeBisen::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('count(*) as total')
        )
        ->where('created_at', '>=', Carbon::now()->subDays(7))
        ->groupBy('date')
        ->orderBy('date', 'desc')
        ->get();

        return response()->json([
            'resume' => [
                'total_commandes'      => $totalCommandes,
                'commandes_aujourd_hui' => $commandesAujourdhui,
                'commandes_ce_mois'    => $commandesCettemois,
            ],
            'par_saisisseur' => $parSaisisseur,
            'par_jour'       => $parJour,
        ]);
    }
}