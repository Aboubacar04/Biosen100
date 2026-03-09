<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Superviseur;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SuperviseurController extends Controller
{
    public function commandesDuJour(Request $request)
    {
        $user = $request->user();
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        $boutiqueId = $user->boutique_id;

        $query = Commande::with(['client', 'employe', 'livreur', 'produits'])
            ->where('boutique_id', $boutiqueId)
            ->whereDate('date_commande', $date);

        // Filtres optionnels
        if ($request->input('statut')) {
            $query->where('statut', $request->input('statut'));
        }
        if ($request->input('statut_livraison')) {
            $sl = $request->input('statut_livraison');
            if ($sl === 'en_attente') {
                $query->where(function ($q) {
                    $q->whereNull('statut_livraison')->orWhere('statut_livraison', 'en_attente');
                });
            } else {
                $query->where('statut_livraison', $sl);
            }
        }
        if ($request->input('employe_id')) {
            $query->where('employe_id', $request->input('employe_id'));
        }
        if ($request->input('livreur_id')) {
            $query->where('livreur_id', $request->input('livreur_id'));
        }
        if ($request->input('type_commande')) {
            $query->where('type_commande', $request->input('type_commande'));
        }

        $commandes = $query->orderBy('created_at', 'desc')->get();

        // Résumé (sans montants)
        $total = $commandes->count();
        $enCours = $commandes->where('statut', 'en_cours')->count();
        $validees = $commandes->where('statut', 'validee')->count();
        $annulees = $commandes->where('statut', 'annulee')->count();
        $livraisons = $commandes->where('type_commande', 'livraison')->count();
        $surPlace = $commandes->where('type_commande', 'sur_place')->count();
        $enAttente = $commandes->filter(function ($c) {
            return $c->type_commande === 'livraison' && (!$c->statut_livraison || $c->statut_livraison === 'en_attente');
        })->count();
        $assignees = $commandes->where('statut_livraison', 'assignee')->count();
        $livrees = $commandes->where('statut_livraison', 'livree')->count();
        $payees = $commandes->where('paye', true)->count();
        $nonPayees = $commandes->where('paye', false)->count();

        return response()->json([
            'resume' => [
                'total'       => $total,
                'en_cours'    => $enCours,
                'validees'    => $validees,
                'annulees'    => $annulees,
                'livraisons'  => $livraisons,
                'sur_place'   => $surPlace,
                'en_attente'  => $enAttente,
                'assignees'   => $assignees,
                'livrees'     => $livrees,
                'payees'      => $payees,
                'non_payees'  => $nonPayees,
            ],
            'commandes' => $commandes,
        ]);
    }
}