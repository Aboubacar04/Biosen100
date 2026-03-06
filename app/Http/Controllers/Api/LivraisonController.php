<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Livreur;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LivraisonController extends Controller
{
    public function enAttente(Request $request)
    {
        $boutiqueId = $request->user()->boutique_id;

        $query = Commande::with(['client', 'employe', 'livreur', 'boutique'])
            ->where('statut', 'validee')
            ->where('type_commande', 'livraison')
            ->where(function ($q) {
                $q->whereNull('statut_livraison')
                  ->orWhere('statut_livraison', 'en_attente');
            });

        if ($boutiqueId) $query->where('boutique_id', $boutiqueId);

        return response()->json($query->orderBy('created_at', 'desc')->get());
    }

    public function toutesLivraisons(Request $request)
    {
        $boutiqueId = $request->user()->boutique_id;

        $baseQuery = Commande::where('statut', 'validee')
            ->where('type_commande', 'livraison');

        if ($boutiqueId) $baseQuery->where('boutique_id', $boutiqueId);

        // Filtre date optionnel
        if ($request->input('date')) {
            $baseQuery->whereDate('date_commande', $request->input('date'));
        }

        // Résumé TOUJOURS sur la query de base sans filtre statut
        $total = (clone $baseQuery)->count();
        $enAttente = (clone $baseQuery)->where(function ($q) {
            $q->whereNull('statut_livraison')
              ->orWhere('statut_livraison', 'en_attente');
        })->count();
        $assignees = (clone $baseQuery)->where('statut_livraison', 'assignee')->count();
        $livrees = (clone $baseQuery)->where('statut_livraison', 'livree')->count();

        // Query filtrée pour la liste
        $filteredQuery = Commande::with(['client', 'employe', 'livreur', 'boutique'])
            ->where('statut', 'validee')
            ->where('type_commande', 'livraison');

        if ($boutiqueId) $filteredQuery->where('boutique_id', $boutiqueId);

        if ($request->input('date')) {
            $filteredQuery->whereDate('date_commande', $request->input('date'));
        }

        $statutFilter = $request->input('statut_livraison');
        if ($statutFilter) {
            if ($statutFilter === 'en_attente') {
                $filteredQuery->where(function ($q) {
                    $q->whereNull('statut_livraison')
                      ->orWhere('statut_livraison', 'en_attente');
                });
            } else {
                $filteredQuery->where('statut_livraison', $statutFilter);
            }
        }

        return response()->json([
            'resume' => [
                'total' => $total,
                'en_attente' => $enAttente,
                'assignees' => $assignees,
                'livrees' => $livrees,
            ],
            'commandes' => $filteredQuery->orderBy('created_at', 'desc')->get(),
        ]);
    }

    public function assigner(Request $request, Commande $commande)
    {
        $request->validate([
            'livreur_id' => 'required|exists:livreurs,id',
        ]);

        if ($commande->type_commande !== 'livraison') {
            return response()->json(['message' => 'Cette commande n\'est pas une livraison'], 400);
        }

        if ($commande->statut !== 'validee') {
            return response()->json(['message' => 'La commande doit être validée'], 400);
        }

        $commande->livreur_id = $request->livreur_id;
        $commande->statut_livraison = 'assignee';
        $commande->save();

        Log::info('Commande assignée', [
            'commande_id' => $commande->id,
            'livreur_id' => $commande->livreur_id,
            'statut_livraison' => $commande->statut_livraison,
        ]);

        return response()->json([
            'message' => 'Livreur assigné avec succès',
            'commande' => $commande->fresh()->load(['client', 'livreur', 'boutique']),
        ]);
    }

    public function mesLivraisons(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Livreur)) {
            return response()->json(['message' => 'Accès réservé aux livreurs'], 403);
        }

        // Toutes les commandes assignées non livrées (pas de filtre date)
        $aLivrer = Commande::with(['client', 'employe', 'boutique'])
            ->where('livreur_id', $user->id)
            ->where('statut', 'validee')
            ->where('statut_livraison', 'assignee')
            ->orderBy('created_at', 'desc')
            ->get();

        // Livrées aujourd'hui
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $livrees = Commande::with(['client', 'employe', 'boutique'])
            ->where('livreur_id', $user->id)
            ->where('statut_livraison', 'livree')
            ->whereDate('date_livraison', $date)
            ->orderBy('date_livraison', 'desc')
            ->get();

        return response()->json([
            'resume' => [
                'a_livrer' => $aLivrer->count(),
                'livrees' => $livrees->count(),
                'total_a_livrer' => $aLivrer->sum('total'),
                'total_livrees' => $livrees->sum('total'),
            ],
            'a_livrer' => $aLivrer,
            'livrees' => $livrees,
        ]);
    }

    public function marquerLivree(Request $request, Commande $commande)
    {
        $user = $request->user();

        if ($user instanceof Livreur && $commande->livreur_id !== $user->id) {
            return response()->json(['message' => 'Cette commande ne vous est pas assignée'], 403);
        }

        if ($commande->statut_livraison !== 'assignee') {
            return response()->json(['message' => 'Cette commande n\'est pas en cours de livraison'], 400);
        }

        $commande->statut_livraison = 'livree';
        $commande->date_livraison = Carbon::now();
        $commande->save();

        Log::info('Commande livrée', [
            'commande_id' => $commande->id,
            'livreur_id' => $user->id,
            'statut_livraison' => $commande->statut_livraison,
            'date_livraison' => $commande->date_livraison,
        ]);

        return response()->json([
            'message' => 'Commande marquée comme livrée',
            'commande' => $commande->fresh()->load(['client', 'boutique']),
        ]);
    }

    public function livreurs(Request $request)
    {
        $boutiqueId = $request->user()->boutique_id;

        $query = Livreur::where('actif', true);
        if ($boutiqueId) $query->where('boutique_id', $boutiqueId);

        return response()->json($query->orderBy('nom')->get());
    }
}