<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Livreur;
use Illuminate\Http\Request;

class LivreurController extends Controller
{
    /**
     * 📋 LISTE DES LIVREURS AVEC PAGINATION
     * GET /api/livreurs?boutique_id=&actif=&disponible=&search=&per_page=&page=
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

        // Filtre boutique
        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        // Filtre actif
        if ($actif !== null) {
            $query->where('actif', $actif == '1');
        }

        // Filtre disponible
        if ($disponible !== null) {
            $query->where('disponible', $disponible == '1');
        }

        // 🔍 RECHERCHE par nom OU téléphone
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
     * GET /api/livreurs/disponibles?boutique_id=
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
     * 👁️ AFFICHER UN LIVREUR AVEC STATISTIQUES ET LIVRAISONS
     * GET /api/livreurs/{id}
     */
    public function show(Livreur $livreur)
    {
        // Charger les relations
        $livreur->load('boutique', 'commandes');

        // ═══════════════════════════════════════════════════════
        // 📊 CALCUL DES STATISTIQUES
        // ═══════════════════════════════════════════════════════

        $commandes = $livreur->commandes;

        // Stats générales
        $totalLivraisons = $commandes->count();
        $livraisonsValidees = $commandes->where('statut', 'validee');
        $livraisonsEnCours = $commandes->where('statut', 'en_cours');
        $livraisonsAnnulees = $commandes->where('statut', 'annulee');

        // Montant total livré (seulement commandes validées)
        $montantTotalLivre = $livraisonsValidees->sum(function ($cmd) {
            return (float) $cmd->total;
        });

        // Livraison moyenne
        $livraisonMoyenne = $livraisonsValidees->count() > 0
            ? round($montantTotalLivre / $livraisonsValidees->count(), 2)
            : 0;

        // Dernière livraison validée
        $derniereLivraison = $livraisonsValidees
            ->sortByDesc('date_validation')
            ->first();

        // ═══════════════════════════════════════════════════════
        // 📦 FORMATER LA RÉPONSE
        // ═══════════════════════════════════════════════════════

        return response()->json([
            'livreur' => [
                'id' => $livreur->id,
                'nom' => $livreur->nom,
                'telephone' => $livreur->telephone,
                'disponible' => $livreur->disponible,
                'actif' => $livreur->actif,
                'boutique_id' => $livreur->boutique_id,
                'created_at' => $livreur->created_at,
                'updated_at' => $livreur->updated_at,
                'boutique' => $livreur->boutique,
            ],
            'statistiques' => [
                'total_livraisons' => $totalLivraisons,
                'montant_total_livre' => $montantTotalLivre,
                'livraison_moyenne' => $livraisonMoyenne,
                'derniere_livraison' => $derniereLivraison ? $derniereLivraison->date_validation : null,
                'livraisons_validees' => $livraisonsValidees->count(),
                'livraisons_en_cours' => $livraisonsEnCours->count(),
                'livraisons_annulees' => $livraisonsAnnulees->count(),
            ],
            'commandes' => $commandes->map(function ($cmd) {
                return [
                    'id' => $cmd->id,
                    'numero_commande' => $cmd->numero_commande,
                    'statut' => $cmd->statut,
                    'total' => $cmd->total,
                    'type_commande' => $cmd->type_commande,
                    'notes' => $cmd->notes,
                    'date_commande' => $cmd->date_commande,
                    'created_at' => $cmd->created_at,
                    'client' => $cmd->client ? [
                        'id' => $cmd->client->id,
                        'nom_complet' => $cmd->client->nom_complet,
                        'telephone' => $cmd->client->telephone,
                    ] : null,
                ];
            })->values()->all(),
        ]);
    }

    /**
     * ➕ CRÉER UN LIVREUR
     * POST /api/livreurs
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $boutiqueId = $user->isAdmin() ? $request->boutique_id : $user->boutique_id;

        $request->validate([
            'nom'         => 'required|string|max:255',
            'telephone'   => 'required|string|max:20',
            'boutique_id' => $user->isAdmin() ? 'required|exists:boutiques,id' : 'nullable',
        ]);

        $livreur = Livreur::create([
            'nom'         => $request->nom,
            'telephone'   => $request->telephone,
            'boutique_id' => $boutiqueId,
            'disponible'  => true,
            'actif'       => true,
        ]);

        return response()->json([
            'message' => 'Livreur créé avec succès',
            'livreur' => $livreur,
        ], 201);
    }

    /**
     * ✏️ MODIFIER UN LIVREUR
     * PUT /api/livreurs/{id}
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
            'disponible' => 'sometimes|boolean',
            'actif'      => 'sometimes|boolean',
        ]);

        $livreur->update($request->only(['nom', 'telephone', 'disponible', 'actif']));

        return response()->json([
            'message' => 'Livreur modifié avec succès',
            'livreur' => $livreur,
        ]);
    }

    /**
     * 🗑️ SUPPRIMER UN LIVREUR
     * DELETE /api/livreurs/{id}
     */
    public function destroy(Request $request, Livreur $livreur)
    {
        $user = $request->user();

        if (!$user->isAdmin() && (int) $livreur->boutique_id !== (int) $user->boutique_id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer un livreur d\'une autre boutique.'], 403);
        }

        $livreur->delete();

        return response()->json(['message' => 'Livreur supprimé avec succès']);
    }

    /**
     * 🔄 BASCULER DISPONIBILITÉ
     * POST /api/livreurs/{id}/toggle-disponibilite
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
