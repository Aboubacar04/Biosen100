<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class EmployeController extends Controller
{
    /**
     * 📋 LISTE DES EMPLOYÉS AVEC PAGINATION
     * GET /api/employes?boutique_id=&actif=&search=&per_page=&page=
     */
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $search = $request->input('search', '');
        $actif = $request->input('actif');

        $query = Employe::query();

        // Filtre boutique
        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        // Filtre actif
        if ($actif !== null) {
            $query->where('actif', $actif == '1');
        }

        // 🔍 RECHERCHE par nom OU téléphone
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'like', '%' . $search . '%')
                    ->orWhere('telephone', 'like', '%' . $search . '%');
            });
        }

        $perPage = $request->get('per_page', 15);

        // ⚡ PAGINATION avec photo URL complète
        $result = $query->orderBy('created_at', 'desc')->paginate($perPage);

        $result->getCollection()->transform(function ($employe) {
            $employe->photo = $employe->photo ? asset('storage/' . $employe->photo) : null;
            return $employe;
        });

        return $result;
    }

    /**
 * 👁️ AFFICHER UN EMPLOYÉ AVEC STATISTIQUES ET COMMANDES
 * GET /api/employes/{id}
 */
public function show(Request $request, Employe $employe)
{
    $employe->load('boutique');

    // ═══════════════════════════════════════════════════════
    // 📅 FILTRES DATE
    // ═══════════════════════════════════════════════════════
    $dateDebut = $request->query('date_debut');
    $dateFin = $request->query('date_fin');

    // Commandes de base (pour stats)
    $commandesQuery = $employe->commandes()->with('client');

    if ($dateDebut) {
        $commandesQuery->whereDate('created_at', '>=', $dateDebut);
    }
    if ($dateFin) {
        $commandesQuery->whereDate('created_at', '<=', $dateFin);
    }

    $commandes = $commandesQuery->orderBy('created_at', 'desc')->get();

    // ═══════════════════════════════════════════════════════
    // 📊 CALCUL DES STATISTIQUES
    // ═══════════════════════════════════════════════════════
    $totalCommandes = $commandes->count();
    $commandesValidees = $commandes->where('statut', 'validee');
    $commandesEnCours = $commandes->where('statut', 'en_cours');
    $commandesAnnulees = $commandes->where('statut', 'annulee');

    $totalVentes = $commandesValidees->sum(fn($cmd) => (float) $cmd->total);

    $venteMoyenne = $commandesValidees->count() > 0
        ? round($totalVentes / $commandesValidees->count(), 2)
        : 0;

    $derniereCommande = $commandesValidees
        ->sortByDesc('date_validation')
        ->first();

    // Ventes du jour
    $today = now()->toDateString();
    $ventesJour = $commandes
        ->where('statut', 'validee')
        ->filter(fn($cmd) => $cmd->date_validation &&
            \Carbon\Carbon::parse($cmd->date_validation)->toDateString() === $today)
        ->sum(fn($cmd) => (float) $cmd->total);

    // Ventes du mois
    $currentMonth = now()->format('Y-m');
    $ventesMois = $commandes
        ->where('statut', 'validee')
        ->filter(fn($cmd) => $cmd->date_validation &&
            \Carbon\Carbon::parse($cmd->date_validation)->format('Y-m') === $currentMonth)
        ->sum(fn($cmd) => (float) $cmd->total);

    // ═══════════════════════════════════════════════════════
    // 📦 PAGINATION COMMANDES
    // ═══════════════════════════════════════════════════════
    $perPage = (int) $request->query('commandes_per_page', 5);
    $page = (int) $request->query('commandes_page', 1);

    $commandesPaginated = $employe->commandes()
        ->with('client')
        ->when($dateDebut, fn($q) => $q->whereDate('created_at', '>=', $dateDebut))
        ->when($dateFin, fn($q) => $q->whereDate('created_at', '<=', $dateFin))
        ->orderBy('created_at', 'desc')
        ->paginate($perPage, ['*'], 'page', $page);

    // ═══════════════════════════════════════════════════════
    // 📤 RÉPONSE
    // ═══════════════════════════════════════════════════════
    return response()->json([
        'employe' => [
            'id' => $employe->id,
            'nom' => $employe->nom,
            'telephone' => $employe->telephone,
            'photo' => $employe->photo ? asset('storage/' . $employe->photo) : null,
            'actif' => $employe->actif,
            'boutique_id' => $employe->boutique_id,
            'created_at' => $employe->created_at,
            'updated_at' => $employe->updated_at,
            'boutique' => $employe->boutique,
        ],
        'statistiques' => [
            'total_commandes' => $totalCommandes,
            'total_ventes' => $totalVentes,
            'vente_moyenne' => $venteMoyenne,
            'derniere_commande' => $derniereCommande?->date_validation,
            'commandes_validees' => $commandesValidees->count(),
            'commandes_en_cours' => $commandesEnCours->count(),
            'commandes_annulees' => $commandesAnnulees->count(),
            'ventes_jour' => $ventesJour,
            'ventes_mois' => $ventesMois,
        ],
        'commandes' => [
            'data' => $commandesPaginated->map(function ($cmd) {
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
            }),
            'current_page' => $commandesPaginated->currentPage(),
            'last_page' => $commandesPaginated->lastPage(),
            'total' => $commandesPaginated->total(),
            'per_page' => $commandesPaginated->perPage(),
        ],
    ]);
}
    /**
     * ➕ CRÉER UN EMPLOYÉ
     * POST /api/employes (avec photo)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        $boutiqueId = $user->isAdmin() ? $request->boutique_id : $user->boutique_id;

        $request->validate([
            'nom'         => 'required|string|max:255',
            'telephone'   => 'required|string|max:20',
            'photo'       => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'boutique_id' => $user->isAdmin() ? 'required|exists:boutiques,id' : 'nullable',
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('employes', 'public');
        }

        $employe = Employe::create([
            'nom'         => $request->nom,
            'telephone'   => $request->telephone,
            'photo'       => $photoPath,
            'boutique_id' => $boutiqueId,
            'actif'       => true,
        ]);

        // Retourner avec URL complète
        $employe->photo = $employe->photo ? asset('storage/' . $employe->photo) : null;

        return response()->json([
            'message' => 'Employé créé avec succès',
            'employe' => $employe,
        ], 201);
    }

    /**
     * ✏️ MODIFIER UN EMPLOYÉ
     * POST /api/employes/{id} (PUT simulé avec _method)
     */
    public function update(Request $request, Employe $employe)
    {
        $user = $request->user();

        if (!$user->isAdmin() && (int) $employe->boutique_id !== (int) $user->boutique_id) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier un employé d\'une autre boutique.'], 403);
        }

        $request->validate([
            'nom'       => 'sometimes|string|max:255',
            'telephone' => 'sometimes|string|max:20',
            'photo'     => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'actif'     => 'sometimes|boolean',
        ]);

        // Mise à jour des champs
        if ($request->has('nom')) $employe->nom = $request->nom;
        if ($request->has('telephone')) $employe->telephone = $request->telephone;
        if ($request->has('actif')) $employe->actif = $request->actif;

        // Gestion photo
        if ($request->hasFile('photo')) {
            // Supprimer ancienne photo
            if ($employe->photo) {
                $oldPath = str_replace(asset('storage/'), '', $employe->photo);
                Storage::disk('public')->delete($oldPath);
            }
            // Stocker nouvelle photo
            $employe->photo = $request->file('photo')->store('employes', 'public');
        }

        $employe->save();

        // Retourner avec URL complète
        $employe->photo = $employe->photo ? asset('storage/' . $employe->photo) : null;

        return response()->json([
            'message' => 'Employé modifié avec succès',
            'employe' => $employe,
        ]);
    }

    /**
     * 🗑️ SUPPRIMER UN EMPLOYÉ
     * DELETE /api/employes/{id}
     */
    public function destroy(Request $request, Employe $employe)
    {
        $user = $request->user();

        if (!$user->isAdmin() && (int) $employe->boutique_id !== (int) $user->boutique_id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer un employé d\'une autre boutique.'], 403);
        }

        // Supprimer la photo
        if ($employe->photo) {
            $photoPath = str_replace(asset('storage/'), '', $employe->photo);
            Storage::disk('public')->delete($photoPath);
        }

        $employe->delete();

        return response()->json(['message' => 'Employé supprimé avec succès']);
    }
}
