<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Livreur;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LivraisonController extends Controller
{
    public function toutesLivraisons(Request $request)
    {
        $boutiqueId = $request->user()->boutique_id;
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $today = Carbon::today()->format('Y-m-d');

        $dayBase = Commande::where('statut', 'validee')
            ->where('type_commande', 'livraison');
        if ($boutiqueId) $dayBase->where('boutique_id', $boutiqueId);
        $dayBase->whereDate('date_commande', $date);

        $total = (clone $dayBase)->count();
        $enAttente = (clone $dayBase)->where(function ($q) {
            $q->whereNull('statut_livraison')->orWhere('statut_livraison', 'en_attente');
        })->count();
        $assignees = (clone $dayBase)->where('statut_livraison', 'assignee')->count();
        $livrees = (clone $dayBase)->where('statut_livraison', 'livree')->count();

        $enRetardQuery = Commande::where('statut', 'validee')
            ->where('type_commande', 'livraison')
            ->whereDate('date_commande', '<', $today)
            ->where(function ($q) {
                $q->whereNull('statut_livraison')
                  ->orWhere('statut_livraison', 'en_attente')
                  ->orWhere('statut_livraison', 'assignee');
            });
        if ($boutiqueId) $enRetardQuery->where('boutique_id', $boutiqueId);
        $enRetardCount = (clone $enRetardQuery)->count();

        $statutFilter = $request->input('statut_livraison');

        if ($statutFilter === 'en_retard') {
            $commandes = Commande::with(['client', 'employe', 'livreur', 'boutique', 'produits'])
                ->where('statut', 'validee')
                ->where('type_commande', 'livraison')
                ->whereDate('date_commande', '<', $today)
                ->where(function ($q) {
                    $q->whereNull('statut_livraison')
                      ->orWhere('statut_livraison', 'en_attente')
                      ->orWhere('statut_livraison', 'assignee');
                });
            if ($boutiqueId) $commandes->where('boutique_id', $boutiqueId);
            $commandes = $commandes->orderBy('date_commande', 'asc')->get();
        } else {
            $commandes = Commande::with(['client', 'employe', 'livreur', 'boutique', 'produits'])
                ->where('statut', 'validee')
                ->where('type_commande', 'livraison')
                ->whereDate('date_commande', $date);
            if ($boutiqueId) $commandes->where('boutique_id', $boutiqueId);

            if ($statutFilter === 'en_attente') {
                $commandes->where(function ($q) {
                    $q->whereNull('statut_livraison')->orWhere('statut_livraison', 'en_attente');
                });
            } elseif ($statutFilter) {
                $commandes->where('statut_livraison', $statutFilter);
            }

            $commandes = $commandes->orderBy('created_at', 'desc')->get();
        }

        return response()->json([
            'resume' => [
                'total'      => $total,
                'en_attente' => $enAttente,
                'assignees'  => $assignees,
                'livrees'    => $livrees,
                'en_retard'  => $enRetardCount,
            ],
            'commandes' => $commandes,
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

        return response()->json([
            'message' => 'Livreur assigné avec succès',
            'commande' => $commande->fresh()->load(['client', 'livreur', 'boutique', 'produits']),
        ]);
    }

    public function mesLivraisons(Request $request)
    {
        $user = $request->user();

        if (!($user instanceof Livreur)) {
            return response()->json(['message' => 'Accès réservé aux livreurs'], 403);
        }

        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        $today = Carbon::today()->format('Y-m-d');

        $aLivrer = Commande::with(['client', 'employe', 'boutique', 'produits'])
            ->where('livreur_id', $user->id)
            ->where('statut', 'validee')
            ->where('statut_livraison', 'assignee')
            ->whereDate('date_commande', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        $livrees = Commande::with(['client', 'employe', 'boutique', 'produits'])
            ->where('livreur_id', $user->id)
            ->where('statut_livraison', 'livree')
            ->whereDate('date_livraison', $date)
            ->orderBy('date_livraison', 'desc')
            ->get();

        $enRetard = Commande::with(['client', 'employe', 'boutique', 'produits'])
            ->where('livreur_id', $user->id)
            ->where('statut', 'validee')
            ->where('statut_livraison', 'assignee')
            ->whereDate('date_commande', '<', $today)
            ->orderBy('date_commande', 'asc')
            ->get();

        return response()->json([
            'resume' => [
                'a_livrer'        => $aLivrer->count(),
                'livrees'         => $livrees->count(),
                'en_retard'       => $enRetard->count(),
                'total_a_livrer'  => $aLivrer->sum('total'),
                'total_livrees'   => $livrees->sum('total'),
                'total_en_retard' => $enRetard->sum('total'),
            ],
            'a_livrer'  => $aLivrer,
            'livrees'   => $livrees,
            'en_retard' => $enRetard,
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
        // On ne touche PAS au champ paye — il garde sa valeur d'origine
        $commande->save();

        return response()->json([
            'message' => 'Commande marquée comme livrée',
            'commande' => $commande->fresh()->load(['client', 'boutique', 'produits']),
        ]);
    }

    public function annulerLivraison(Request $request, Commande $commande)
    {
        $user = $request->user();

        if ($user instanceof Livreur && $commande->livreur_id !== $user->id) {
            return response()->json(['message' => 'Cette commande ne vous est pas assignée'], 403);
        }

        if ($commande->statut_livraison !== 'livree') {
            return response()->json(['message' => 'Cette commande n\'est pas marquée comme livrée'], 400);
        }

        $commande->statut_livraison = 'assignee';
        $commande->date_livraison = null;
        $commande->save();

        return response()->json([
            'message' => 'Livraison annulée, commande remise en cours',
            'commande' => $commande->fresh()->load(['client', 'boutique', 'produits']),
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