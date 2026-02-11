<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\Produit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommandeController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // 📋 LISTE TOUTES LES COMMANDES
    // GET /api/commandes
    // Paramètres optionnels : ?boutique_id= &statut= &date= &per_page=
    // ─────────────────────────────────────────────────────────────────────────
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $perPage = $request->input('per_page', 15);
        $query   = Commande::with(['client', 'employe', 'livreur']);

        if ($boutiqueId)               $query->where('boutique_id', $boutiqueId);
        if ($request->input('statut')) $query->where('statut', $request->input('statut'));
        if ($request->input('date'))   $query->whereDate('date_commande', $request->input('date'));

        return response()->json($query->orderBy('created_at', 'desc')->paginate($perPage));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ⏳ COMMANDES EN COURS
    // GET /api/commandes/en-cours
    // ─────────────────────────────────────────────────────────────────────────
    public function enCours(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $perPage = $request->input('per_page', 15);
        $query   = Commande::enCours()->with(['client', 'employe', 'livreur', 'produits']);

        if ($boutiqueId) $query->where('boutique_id', $boutiqueId);

        return response()->json($query->orderBy('created_at', 'desc')->paginate($perPage));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ✅ COMMANDES VALIDÉES
    // GET /api/commandes/validees
    // Paramètres optionnels : ?date= &mois= &annee=
    // ─────────────────────────────────────────────────────────────────────────
    public function validees(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $perPage = $request->input('per_page', 15);
        $query   = Commande::validees()->with(['client', 'employe', 'livreur', 'facture']);

        if ($boutiqueId) $query->where('boutique_id', $boutiqueId);

        if ($request->input('date')) {
            $query->whereDate('date_validation', $request->input('date'));
        }

        if ($request->input('mois') && $request->input('annee')) {
            $query->whereMonth('date_validation', $request->input('mois'))
                ->whereYear('date_validation',  $request->input('annee'));
        }

        return response()->json($query->orderBy('date_validation', 'desc')->paginate($perPage));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ❌ COMMANDES ANNULÉES
    // GET /api/commandes/annulees
    // ─────────────────────────────────────────────────────────────────────────
    public function annulees(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $perPage = $request->input('per_page', 15);
        $query   = Commande::annulees()->with(['client', 'employe', 'annuleePar']);

        if ($boutiqueId) $query->where('boutique_id', $boutiqueId);

        return response()->json($query->orderBy('date_annulation', 'desc')->paginate($perPage));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 📅 HISTORIQUE PAR DATE
    // GET /api/commandes/historique?date=2026-02-10
    // ─────────────────────────────────────────────────────────────────────────
    public function historique(Request $request)
    {
        $request->validate(['date' => 'required|date']);

        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $perPage = $request->input('per_page', 15);

        $query = Commande::with(['client', 'employe', 'livreur'])
            ->whereDate('date_commande', $request->date);

        if ($boutiqueId) $query->where('boutique_id', $boutiqueId);

        // Calcul des totaux AVANT pagination
        $totalCommandes = (clone $query)->count();
        $sommeTotal     = (clone $query)->sum('total');
        $totalValidees  = (clone $query)->where('statut', 'validee')->sum('total');
        $nbAnnulees     = (clone $query)->where('statut', 'annulee')->count();
        $nbEnCours      = (clone $query)->where('statut', 'en_cours')->count();

        $commandes = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'resume' => [
                'date'            => $request->date,
                'total_commandes' => $totalCommandes,
                'somme_totale'    => $sommeTotal,
                'total_validees'  => $totalValidees,
                'nb_en_cours'     => $nbEnCours,
                'nb_annulees'     => $nbAnnulees,
            ],
            'commandes' => $commandes,
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 👷 COMMANDES DU JOUR D'UN EMPLOYÉ  ← NOUVELLE MÉTHODE
    // GET /api/commandes/employe/{employe_id}/du-jour
    // Paramètre optionnel : ?date=2026-02-10  (aujourd'hui par défaut)
    // Paramètre optionnel : ?boutique_id=  (admin uniquement)
    // ─────────────────────────────────────────────────────────────────────────
    public function commandesDuJourEmploye(Request $request, $employeId)
    {
        if ($request->has('date')) {
            $request->validate(['date' => 'date_format:Y-m-d']);
        }

        $date = $request->input('date', Carbon::today()->format('Y-m-d'));

        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Commande::where('employe_id', $employeId)
            ->whereDate('date_commande', $date)   // ✅ CORRIGÉ
            ->with(['client', 'livreur', 'produits', 'employe']);

        if ($boutiqueId) $query->where('boutique_id', $boutiqueId);

        $commandes = $query->orderBy('date_commande', 'desc')->get();

        $validees = $commandes->where('statut', 'validee');
        $enCours  = $commandes->where('statut', 'en_cours');
        $annulees = $commandes->where('statut', 'annulee');

        return response()->json([
            'employe' => $commandes->isNotEmpty() ? [
                'id'        => $commandes->first()->employe->id,
                'nom'       => $commandes->first()->employe->nom,
                'telephone' => $commandes->first()->employe->telephone,
            ] : null,
            'date'   => $date,
            'resume' => [
                'total_commandes'    => $commandes->count(),
                'commandes_validees' => $validees->count(),
                'commandes_en_cours' => $enCours->count(),
                'commandes_annulees' => $annulees->count(),
                'total_ventes'       => round($validees->sum('total'), 2),
            ],
            'commandes' => $commandes->map(function ($commande) {
                return [
                    'id'            => $commande->id,
                    'statut'        => $commande->statut,
                    'total'         => $commande->total,
                    'type_commande' => $commande->type_commande,
                    'notes'         => $commande->notes,
                    'heure'         => $commande->created_at->format('H:i'),
                    'client'        => $commande->client ? [
                        'id'          => $commande->client->id,
                        'nom_complet' => $commande->client->nom_complet,
                        'telephone'   => $commande->client->telephone,
                    ] : null,
                    'livreur'       => $commande->livreur ? [
                        'id'  => $commande->livreur->id,
                        'nom' => $commande->livreur->nom,
                    ] : null,
                    'produits'      => $commande->produits->map(fn($p) => [
                        'id'            => $p->id,
                        'nom'           => $p->nom,
                        'quantite'      => $p->pivot->quantite,
                        'prix_unitaire' => $p->pivot->prix_unitaire,
                        'sous_total'    => $p->pivot->sous_total,
                    ]),
                ];
            }),
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ➕ CRÉER UNE COMMANDE
    // POST /api/commandes
    // ─────────────────────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $request->validate([
            'boutique_id'           => 'required|exists:boutiques,id',
            'client_id'             => 'nullable|exists:clients,id',
            'employe_id'            => 'required|exists:employes,id',
            'livreur_id'            => 'nullable|exists:livreurs,id',
            'type_commande'         => 'required|in:sur_place,livraison',
            'notes'                 => 'nullable|string',
            'produits'              => 'required|array|min:1',
            'produits.*.produit_id' => 'required|exists:produits,id',
            'produits.*.quantite'   => 'required|integer|min:1',
        ]);

        DB::beginTransaction();
        try {
            $commande = Commande::create([
                'boutique_id'   => $request->boutique_id,
                'client_id'     => $request->client_id,
                'employe_id'    => $request->employe_id,
                'livreur_id'    => $request->livreur_id,
                'type_commande' => $request->type_commande,
                'notes'         => $request->notes,
                'total'         => 0,
            ]);

            $total = 0;
            foreach ($request->produits as $item) {
                $produit   = Produit::findOrFail($item['produit_id']);
                $quantite  = $item['quantite'];
                $prixUnit  = $produit->prix_vente;
                $sousTotal = $prixUnit * $quantite;

                $commande->produits()->attach($produit->id, [
                    'quantite'      => $quantite,
                    'prix_unitaire' => $prixUnit,
                    'sous_total'    => $sousTotal,
                ]);
                $total += $sousTotal;
            }

            $commande->update(['total' => $total]);
            DB::commit();

            return response()->json([
                'message'  => 'Commande créée avec succès',
                'commande' => $commande->load(['produits', 'client', 'employe', 'livreur']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 👁️ AFFICHER UNE COMMANDE
    // GET /api/commandes/{commande}
    // ─────────────────────────────────────────────────────────────────────────
    public function show(Commande $commande)
    {
        return response()->json(
            $commande->load(['boutique', 'client', 'employe', 'livreur', 'produits', 'facture', 'annuleePar'])
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ✅ VALIDER UNE COMMANDE
    // POST /api/commandes/{commande}/valider
    // ─────────────────────────────────────────────────────────────────────────
    public function valider(Commande $commande)
    {
        if ($commande->statut !== 'en_cours') {
            return response()->json(['message' => 'Seules les commandes en cours peuvent être validées'], 400);
        }

        DB::beginTransaction();
        try {
            $commande->valider();
            DB::commit();

            $commande->load(['boutique', 'client', 'produits', 'facture']);

            return response()->json([
                'message'    => 'Commande validée avec succès',
                'impression' => [
                    'numero_facture' => $commande->facture->numero,
                    'date_emission'  => $commande->facture->created_at->format('d/m/Y H:i'),
                    'boutique'       => [
                        'nom'       => $commande->boutique->nom,
                        'adresse'   => $commande->boutique->adresse,
                        'telephone' => $commande->boutique->telephone,
                    ],
                    'client'         => $commande->client ? [
                        'nom'       => $commande->client->nom_complet,
                        'telephone' => $commande->client->telephone,
                    ] : null,
                    'type_commande'  => $commande->type_commande,
                    'produits'       => $commande->produits->map(fn($p) => [
                        'nom'           => $p->nom,
                        'quantite'      => $p->pivot->quantite,
                        'prix_unitaire' => $p->pivot->prix_unitaire,
                        'sous_total'    => $p->pivot->sous_total,
                    ]),
                    'total'          => $commande->total,
                    'notes'          => $commande->notes,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur lors de la validation', 'error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ❌ ANNULER UNE COMMANDE
    // POST /api/commandes/{commande}/annuler
    // Body : { "raison": "..." }
    // ─────────────────────────────────────────────────────────────────────────
    public function annuler(Request $request, Commande $commande)
    {
        $request->validate(['raison' => 'required|string']);

        DB::beginTransaction();
        try {
            $commande->annuler($request->raison, $request->user()->id);
            DB::commit();

            return response()->json([
                'message'  => 'Commande annulée avec succès',
                'commande' => $commande->load('annuleePar'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ✏️ MODIFIER UNE COMMANDE EN COURS
    // PUT /api/commandes/{commande}
    // ─────────────────────────────────────────────────────────────────────────
    public function update(Request $request, Commande $commande)
    {
        if ($commande->statut !== 'en_cours') {
            return response()->json(['message' => 'Seules les commandes en cours peuvent être modifiées'], 400);
        }

        $request->validate([
            'client_id'             => 'nullable|exists:clients,id',
            'employe_id'            => 'sometimes|exists:employes,id',
            'livreur_id'            => 'nullable|exists:livreurs,id',
            'type_commande'         => 'sometimes|in:sur_place,livraison',
            'notes'                 => 'nullable|string',
            'produits'              => 'sometimes|array|min:1',
            'produits.*.produit_id' => 'required|exists:produits,id',
            'produits.*.quantite'   => 'required|integer|min:1',
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
                    $produit   = Produit::findOrFail($item['produit_id']);
                    $quantite  = $item['quantite'];
                    $prixUnit  = $produit->prix_vente;
                    $sousTotal = $prixUnit * $quantite;

                    $commande->produits()->attach($produit->id, [
                        'quantite'      => $quantite,
                        'prix_unitaire' => $prixUnit,
                        'sous_total'    => $sousTotal,
                    ]);
                    $total += $sousTotal;
                }
                $commande->update(['total' => $total]);
            }

            DB::commit();

            return response()->json([
                'message'  => 'Commande modifiée avec succès',
                'commande' => $commande->load(['produits', 'client', 'employe', 'livreur']),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Erreur', 'error' => $e->getMessage()], 500);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 🗑️ SUPPRIMER UNE COMMANDE EN COURS
    // DELETE /api/commandes/{commande}
    // ─────────────────────────────────────────────────────────────────────────
    public function destroy(Commande $commande)
    {
        if ($commande->statut !== 'en_cours') {
            return response()->json(['message' => 'Seules les commandes en cours peuvent être supprimées'], 400);
        }

        $commande->delete();

        return response()->json(['message' => 'Commande supprimée avec succès']);
    }
}
