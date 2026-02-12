<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Depense;
use App\Models\Produit;
use App\Models\Employe;
use App\Models\Livreur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    private function getBoutiqueId(Request $request)
    {
        return $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;
    }

    public function stats(Request $request)
    {
        $boutiqueId = $this->getBoutiqueId($request);

        $queryVentesJour = Commande::validees()->duJour();
        if ($boutiqueId) $queryVentesJour->where('boutique_id', $boutiqueId);
        $ventesJour = $queryVentesJour->sum('total');

        $queryDepensesJour = Depense::duJour();
        if ($boutiqueId) $queryDepensesJour->where('boutique_id', $boutiqueId);
        $depensesJour = $queryDepensesJour->sum('montant');

        $queryVentesMois = Commande::validees()->duMois();
        if ($boutiqueId) $queryVentesMois->where('boutique_id', $boutiqueId);
        $ventesMois = $queryVentesMois->sum('total');

        $queryDepensesMois = Depense::duMois();
        if ($boutiqueId) $queryDepensesMois->where('boutique_id', $boutiqueId);
        $depensesMois = $queryDepensesMois->sum('montant');

        $queryVentesAnnee = Commande::validees()->deLAnnee();
        if ($boutiqueId) $queryVentesAnnee->where('boutique_id', $boutiqueId);
        $ventesAnnee = $queryVentesAnnee->sum('total');

        $queryDepensesAnnee = Depense::deLAnnee();
        if ($boutiqueId) $queryDepensesAnnee->where('boutique_id', $boutiqueId);
        $depensesAnnee = $queryDepensesAnnee->sum('montant');

        $queryCommandesJour = Commande::validees()->duJour();
        if ($boutiqueId) $queryCommandesJour->where('boutique_id', $boutiqueId);
        $nombreCommandesJour = $queryCommandesJour->count();

        $queryCommandesMois = Commande::validees()->duMois();
        if ($boutiqueId) $queryCommandesMois->where('boutique_id', $boutiqueId);
        $nombreCommandesMois = $queryCommandesMois->count();

        $queryEnCours = Commande::enCours();
        if ($boutiqueId) $queryEnCours->where('boutique_id', $boutiqueId);
        $commandesEnCours = $queryEnCours->count();

        return response()->json([
            'jour' => [
                'ventes' => $ventesJour,
                'depenses' => $depensesJour,
                'benefice' => $ventesJour - $depensesJour,
                'nombre_commandes' => $nombreCommandesJour,
            ],
            'mois' => [
                'ventes' => $ventesMois,
                'depenses' => $depensesMois,
                'benefice' => $ventesMois - $depensesMois,
                'nombre_commandes' => $nombreCommandesMois,
            ],
            'annee' => [
                'ventes' => $ventesAnnee,
                'depenses' => $depensesAnnee,
                'benefice' => $ventesAnnee - $depensesAnnee,
            ],
            'commandes_en_cours' => $commandesEnCours,
        ]);
    }

    public function topProduits(Request $request)
    {
        $boutiqueId = $this->getBoutiqueId($request);
        $limit = $request->input('limit', 10);
        $periode = $request->input('periode', 'mois');

        $query = DB::table('commande_produit')
            ->join('produits', 'commande_produit.produit_id', '=', 'produits.id')
            ->join('commandes', 'commande_produit.commande_id', '=', 'commandes.id')
            ->where('commandes.statut', 'validee');

        if ($boutiqueId) {
            $query->where('commandes.boutique_id', $boutiqueId);
        }

        switch ($periode) {
            case 'jour':
                $query->whereDate('commandes.date_commande', today());
                break;
            case 'semaine':
                $query->whereBetween('commandes.date_commande', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'mois':
                $query->whereMonth('commandes.date_commande', now()->month)
                    ->whereYear('commandes.date_commande', now()->year);
                break;
            case 'annee':
                $query->whereYear('commandes.date_commande', now()->year);
                break;
        }

        $topProduits = $query->select(
            'produits.id',
            'produits.nom',
            'produits.image',
            DB::raw('SUM(commande_produit.quantite) as quantite_vendue'),
            DB::raw('SUM(commande_produit.sous_total) as total_ventes')
        )
            ->groupBy('produits.id', 'produits.nom', 'produits.image')
            ->orderBy('quantite_vendue', 'desc')
            ->limit($limit)
            ->get();

        return response()->json($topProduits);
    }

    public function topEmployes(Request $request)
    {
        $boutiqueId = $this->getBoutiqueId($request);
        $limit = $request->input('limit', 10);
        $periode = $request->input('periode', 'mois');

        $query = Commande::validees()->with('employe');

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        switch ($periode) {
            case 'jour':
                $query->whereDate('date_commande', today());
                break;
            case 'semaine':
                $query->whereBetween('date_commande', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'mois':
                $query->whereMonth('date_commande', now()->month)
                    ->whereYear('date_commande', now()->year);
                break;
            case 'annee':
                $query->whereYear('date_commande', now()->year);
                break;
        }

        $topEmployes = $query->select(
            'employe_id',
            DB::raw('COUNT(*) as nombre_commandes'),
            DB::raw('SUM(total) as total_ventes')
        )
            ->groupBy('employe_id')
            ->orderBy('nombre_commandes', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($item) {
                $employe = Employe::find($item->employe_id);
                return [
                    'employe_id' => $item->employe_id,
                    'nom' => $employe->nom,
                    'photo' => $employe->photo,
                    'nombre_commandes' => $item->nombre_commandes,
                    'total_ventes' => $item->total_ventes,
                ];
            });

        return response()->json($topEmployes);
    }

    public function topLivreurs(Request $request)
    {
        $boutiqueId = $this->getBoutiqueId($request);
        $limit = $request->input('limit', 10);
        $periode = $request->input('periode', 'mois');

        $query = Commande::validees()
            ->where('type_commande', 'livraison')
            ->whereNotNull('livreur_id')
            ->with('livreur');

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        switch ($periode) {
            case 'jour':
                $query->whereDate('date_commande', today());
                break;
            case 'semaine':
                $query->whereBetween('date_commande', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'mois':
                $query->whereMonth('date_commande', now()->month)
                    ->whereYear('date_commande', now()->year);
                break;
            case 'annee':
                $query->whereYear('date_commande', now()->year);
                break;
        }

        $topLivreurs = $query->select(
            'livreur_id',
            DB::raw('COUNT(*) as nombre_livraisons'),
            DB::raw('SUM(total) as total_livraisons')
        )
            ->groupBy('livreur_id')
            ->orderBy('nombre_livraisons', 'desc')
            ->limit($limit)
            ->get()
            ->map(function($item) {
                $livreur = Livreur::find($item->livreur_id);
                return [
                    'livreur_id' => $item->livreur_id,
                    'nom' => $livreur->nom,
                    'telephone' => $livreur->telephone,
                    'nombre_livraisons' => $item->nombre_livraisons,
                    'total_livraisons' => $item->total_livraisons,
                ];
            });

        return response()->json($topLivreurs);
    }

    public function commandesSemaine(Request $request)
    {
        $boutiqueId = $this->getBoutiqueId($request);

        $joursEnFrancais = [
            'Monday' => 'Lundi',
            'Tuesday' => 'Mardi',
            'Wednesday' => 'Mercredi',
            'Thursday' => 'Jeudi',
            'Friday' => 'Vendredi',
            'Saturday' => 'Samedi',
            'Sunday' => 'Dimanche',
        ];

        $commandesParJour = [];
        $debut = now()->startOfWeek();

        for ($i = 0; $i < 7; $i++) {
            $jour = $debut->copy()->addDays($i);
            $jourEnFrancais = $joursEnFrancais[$jour->format('l')];

            $queryCommandes = Commande::validees()->whereDate('date_commande', $jour);
            if ($boutiqueId) $queryCommandes->where('boutique_id', $boutiqueId);
            $nombreCommandes = $queryCommandes->count();

            $queryVentes = Commande::validees()->whereDate('date_commande', $jour);
            if ($boutiqueId) $queryVentes->where('boutique_id', $boutiqueId);
            $totalVentes = $queryVentes->sum('total');

            $commandesParJour[] = [
                'jour' => $jourEnFrancais,
                'date' => $jour->format('Y-m-d'),
                'nombre_commandes' => $nombreCommandes,
                'total_ventes' => $totalVentes,
            ];
        }

        return response()->json($commandesParJour);
    }

    public function commandesMois(Request $request)
    {
        $boutiqueId = $this->getBoutiqueId($request);

        $commandesParJour = [];
        $joursInMois = now()->daysInMonth;

        for ($i = 1; $i <= $joursInMois; $i++) {
            $date = now()->setDay($i);

            $queryCommandes = Commande::validees()->whereDate('date_commande', $date);
            if ($boutiqueId) $queryCommandes->where('boutique_id', $boutiqueId);
            $nombreCommandes = $queryCommandes->count();

            $queryVentes = Commande::validees()->whereDate('date_commande', $date);
            if ($boutiqueId) $queryVentes->where('boutique_id', $boutiqueId);
            $totalVentes = $queryVentes->sum('total');

            $commandesParJour[] = [
                'jour' => $i,
                'date' => $date->format('Y-m-d'),
                'nombre_commandes' => $nombreCommandes,
                'total_ventes' => $totalVentes,
            ];
        }

        return response()->json($commandesParJour);
    }

    public function evolutionVentes(Request $request)
    {
        $boutiqueId = $this->getBoutiqueId($request);
        $evolution = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');

            $queryVentes = Commande::validees()->whereDate('date_commande', $date);
            if ($boutiqueId) $queryVentes->where('boutique_id', $boutiqueId);
            $ventes = $queryVentes->sum('total');

            $queryCommandes = Commande::validees()->whereDate('date_commande', $date);
            if ($boutiqueId) $queryCommandes->where('boutique_id', $boutiqueId);
            $nombreCommandes = $queryCommandes->count();

            $evolution[] = [
                'date' => $date,
                'ventes' => $ventes,
                'nombre_commandes' => $nombreCommandes,
            ];
        }

        return response()->json($evolution);
    }

    public function stockFaible(Request $request)
    {
        $boutiqueId = $this->getBoutiqueId($request);

        $query = Produit::stockFaible()->with('categorie');

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $produits = $query->get();

        return response()->json($produits);
    }

    public function statsEmploye(Request $request, $employeId)
    {
        $periode = $request->input('periode', 'mois');
        $perPage = $request->input('per_page', 15);
        $search = $request->input('search', '');

        $query = Commande::validees()->where('employe_id', $employeId);

        // Filtrer par période
        switch ($periode) {
            case 'jour':
                $query->whereDate('date_commande', today());
                break;
            case 'semaine':
                $query->whereBetween('date_commande', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'mois':
                $query->whereMonth('date_commande', now()->month)
                    ->whereYear('date_commande', now()->year);
                break;
            case 'annee':
                $query->whereYear('date_commande', now()->year);
                break;
        }

        // Recherche
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('numero_commande', 'like', '%' . $search . '%')
                    ->orWhereHas('client', function ($clientQuery) use ($search) {
                        $clientQuery->where('nom_complet', 'like', '%' . $search . '%')
                            ->orWhere('telephone', 'like', '%' . $search . '%');
                    });
            });
        }

        // Calcul stats (avant pagination)
        $totalCommandes = $query->count();
        $totalVentes = $query->sum('total');

        // Pagination
        $commandes = $query->with('client')->latest('date_commande')->paginate($perPage);

        return response()->json([
            'nombre_commandes' => $totalCommandes,
            'total_ventes' => $totalVentes,
            'commandes' => $commandes,
        ]);
    }
}
