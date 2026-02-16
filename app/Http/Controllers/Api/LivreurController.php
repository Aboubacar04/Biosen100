<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Livreur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class LivreurController extends Controller
{
    /**
     * 📋 LISTE DES LIVREURS AVEC PAGINATION
     */
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $search = $request->input('search', '');
        $actif = $request->input('actif');
        $disponible = $request->input('disponible');

        $query = Livreur::query();

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }
        if ($actif !== null) {
            $query->where('actif', $actif == '1');
        }
        if ($disponible !== null) {
            $query->where('disponible', $disponible == '1');
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', '%' . $search . '%')
                    ->orWhere('telephone', 'like', '%' . $search . '%');
            });
        }

        $perPage = $request->get('per_page', 15);
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * 🚚 LIVREURS DISPONIBLES
     */
    public function disponibles(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Livreur::where('disponible', true)->where('actif', true);

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        return response()->json($query->get());
    }

    /**
     * 👁️ AFFICHER UN LIVREUR — STATS FILTRÉES PAR DATE + COMMANDES PAGINÉES
     *
     * GET /api/livreurs/{id}?date_debut=2025-01-01&date_fin=2025-12-31&commandes_page=1&commandes_per_page=5
     */
    public function show(Request $request, Livreur $livreur)
    {
        $livreur->load('boutique');

        // ═══ FILTRES DATE ═══
        $dateDebut = $request->input('date_debut');
        $dateFin   = $request->input('date_fin');

        // ═══ STATISTIQUES (sur toutes les commandes filtrées par date) ═══
        $statsQuery = $livreur->commandes();
        if ($dateDebut) {
            $statsQuery->whereDate('created_at', '>=', $dateDebut);
        }
        if ($dateFin) {
            $statsQuery->whereDate('created_at', '<=', $dateFin);
        }
        $allFiltered = $statsQuery->get();

        $totalLivraisons    = $allFiltered->count();
        $livraisonsValidees = $allFiltered->where('statut', 'validee');
        $livraisonsEnCours  = $allFiltered->where('statut', 'en_cours');
        $livraisonsAnnulees = $allFiltered->where('statut', 'annulee');

        $montantTotalLivre = $livraisonsValidees->sum(fn($cmd) => (float) $cmd->total);

        $livraisonMoyenne = $livraisonsValidees->count() > 0
            ? round($montantTotalLivre / $livraisonsValidees->count(), 2)
            : 0;

        $derniereLivraison = $livraisonsValidees->sortByDesc('date_validation')->first();

        // Ventes du jour
        $today = now()->toDateString();
        $ventesJour = $allFiltered->where('statut', 'validee')
            ->filter(fn($cmd) => $cmd->date_validation && Carbon::parse($cmd->date_validation)->toDateString() === $today)
            ->sum(fn($cmd) => (float) $cmd->total);

        // Ventes du mois
        $currentMonth = now()->format('Y-m');
        $ventesMois = $allFiltered->where('statut', 'validee')
            ->filter(fn($cmd) => $cmd->date_validation && Carbon::parse($cmd->date_validation)->format('Y-m') === $currentMonth)
            ->sum(fn($cmd) => (float) $cmd->total);

        // ═══ COMMANDES PAGINÉES (même filtre date) ═══
        $commandesQuery = $livreur->commandes()
            ->with('client')
            ->orderBy('created_at', 'desc');

        if ($dateDebut) {
            $commandesQuery->whereDate('created_at', '>=', $dateDebut);
        }
        if ($dateFin) {
            $commandesQuery->whereDate('created_at', '<=', $dateFin);
        }

        $perPage  = (int) $request->input('commandes_per_page', 5);
        $page     = (int) $request->input('commandes_page', 1);
        $paginated = $commandesQuery->paginate($perPage, ['*'], 'commandes_page', $page);

        // ═══ RÉPONSE ═══
        return response()->json([
            'livreur' => [
                'id'          => $livreur->id,
                'nom'         => $livreur->nom,
                'telephone'   => $livreur->telephone,
                'disponible'  => $livreur->disponible,
                'photo'       => $livreur->photo,
                'actif'       => $livreur->actif,
                'boutique_id' => $livreur->boutique_id,
                'created_at'  => $livreur->created_at,
                'updated_at'  => $livreur->updated_at,
                'boutique'    => $livreur->boutique,
            ],
            'statistiques' => [
                'total_livraisons'    => $totalLivraisons,
                'montant_total_livre' => $montantTotalLivre,
                'livraison_moyenne'   => $livraisonMoyenne,
                'derniere_livraison'  => $derniereLivraison ? $derniereLivraison->date_validation : null,
                'livraisons_validees' => $livraisonsValidees->count(),
                'livraisons_en_cours' => $livraisonsEnCours->count(),
                'livraisons_annulees' => $livraisonsAnnulees->count(),
                'ventes_jour'         => $ventesJour,
                'ventes_mois'         => $ventesMois,
            ],
            'commandes' => [
                'data' => $paginated->map(function ($cmd) {
                    return [
                        'id'               => $cmd->id,
                        'numero_commande'  => $cmd->numero_commande,
                        'statut'           => $cmd->statut,
                        'total'            => $cmd->total,
                        'type_commande'    => $cmd->type_commande,
                        'notes'            => $cmd->notes,
                        'date_commande'    => $cmd->date_commande,
                        'created_at'       => $cmd->created_at,
                        'client'           => $cmd->client ? [
                            'id'           => $cmd->client->id,
                            'nom_complet'  => $cmd->client->nom_complet,
                            'telephone'    => $cmd->client->telephone,
                        ] : null,
                    ];
                })->values()->all(),
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'total'        => $paginated->total(),
                'from'         => $paginated->firstItem(),
                'to'           => $paginated->lastItem(),
            ],
        ]);
    }

    /**
     * ➕ CRÉER UN LIVREUR
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $boutiqueId = $user->isAdmin() ? $request->boutique_id : $user->boutique_id;

        $request->validate([
            'nom'         => 'required|string|max:255',
            'telephone'   => 'required|string|max:20',
            'photo'       => 'nullable|image|max:2048',
            'boutique_id' => $user->isAdmin() ? 'required|exists:boutiques,id' : 'nullable',
        ]);

        $data = [
            'nom'         => $request->nom,
            'telephone'   => $request->telephone,
            'boutique_id' => $boutiqueId,
            'disponible'  => true,
            'actif'       => true,
        ];

        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('livreurs', 'public');
            $data['photo'] = $path;
        }

        $livreur = Livreur::create($data);

        return response()->json([
            'message' => 'Livreur créé avec succès',
            'livreur' => $livreur,
        ], 201);
    }

    /**
     * ✏️ MODIFIER UN LIVREUR
     */
    public function update(Request $request, Livreur $livreur)
    {
        $user = $request->user();

        if (!$user->isAdmin() && (int) $livreur->boutique_id !== (int) $user->boutique_id) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier un livreur d\'une autre boutique.'], 403);
        }

        $request->validate([
            'nom'        => 'sometimes|string|max:255',
            'telephone'  => 'sometimes|string|max:20',
            'photo'      => 'nullable|image|max:2048',
            'disponible' => 'sometimes|boolean',
            'actif'      => 'sometimes|boolean',
        ]);

        $livreur->fill($request->only(['nom', 'telephone', 'disponible', 'actif']));

        if ($request->hasFile('photo')) {
            if ($livreur->photo) {
                Storage::disk('public')->delete($livreur->photo);
            }
            $path = $request->file('photo')->store('livreurs', 'public');
            $livreur->photo = $path;
        }

        $livreur->save();

        return response()->json([
            'message' => 'Livreur modifié avec succès',
            'livreur' => $livreur,
        ]);
    }

    /**
     * 🗑️ SUPPRIMER UN LIVREUR
     */
    public function destroy(Request $request, Livreur $livreur)
    {
        $user = $request->user();

        if (!$user->isAdmin() && (int) $livreur->boutique_id !== (int) $user->boutique_id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer un livreur d\'une autre boutique.'], 403);
        }

        if ($livreur->photo) {
            Storage::disk('public')->delete($livreur->photo);
        }

        $livreur->delete();

        return response()->json(['message' => 'Livreur supprimé avec succès']);
    }

    /**
     * 🔄 BASCULER DISPONIBILITÉ
     */
    public function toggleDisponibilite(Livreur $livreur)
    {
        $livreur->disponible = !$livreur->disponible;
        $livreur->save();

        return response()->json([
            'message' => 'Disponibilité mise à jour',
            'livreur' => $livreur,
        ]);
    }
}