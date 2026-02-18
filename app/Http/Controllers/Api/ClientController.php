<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 15);

        $query = Client::withCount('commandes');

        if ($search) {
            $searchClean = $this->normalizePhone($search);
            $query->where(function ($q) use ($search, $searchClean) {
                $q->where('nom_complet', 'like', '%' . $search . '%')
                    ->orWhere('telephone', 'like', '%' . $searchClean . '%')
                    ->orWhere('telephone', 'like', '%' . $search . '%');
            });
        }

        return response()->json($query->latest()->paginate($perPage));
    }

    public function autocomplete(Request $request)
    {
        $q = $request->input('q', '');

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $qClean = $this->normalizePhone($q);
        $clients = Client::where(function ($query) use ($q, $qClean) {
            $query->where('nom_complet', 'like', '%' . $q . '%')
                ->orWhere('telephone', 'like', '%' . $qClean . '%')
                ->orWhere('telephone', 'like', '%' . $q . '%');
        })
            ->limit(10)
            ->get(['id', 'nom_complet', 'telephone', 'adresse']);

        return response()->json($clients);
    }

    public function search(Request $request)
    {
        $request->validate(['telephone' => 'required|string']);

        $telephone = $this->normalizePhone($request->telephone);

        $client = Client::where('telephone', $telephone)->first();

        if (!$client) {
            return response()->json(['message' => 'Client non trouvé'], 404);
        }

        return response()->json($client);
    }

    public function rechercherParTelephone(Request $request)
    {
        $telephone = $request->input('telephone');

        if (!$telephone) {
            return response()->json(['message' => 'Numéro de téléphone requis'], 400);
        }

        $telephoneClean = $this->normalizePhone($telephone);

        $client = Client::where('telephone', $telephoneClean)->first();

        if (!$client) {
            return response()->json(['message' => 'Client non trouvé'], 404);
        }

        return response()->json($client);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $boutiqueId = $user->isAdmin() ? $request->boutique_id : $user->boutique_id;

        $request->validate([
            'nom_complet' => 'required|string|max:255',
            'telephone'   => 'required|string|max:20',
            'adresse'     => 'nullable|string',
            'boutique_id' => $user->isAdmin() ? 'required|exists:boutiques,id' : 'nullable',
        ]);

        $telephoneClean = $this->normalizePhone($request->telephone);

        // Vérification globale : le numéro existe déjà dans TOUTE la base
        $exists = Client::where('telephone', $telephoneClean)->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Ce numéro de téléphone existe déjà.',
                'errors' => [
                    'telephone' => ['Ce numéro de téléphone est déjà utilisé par un autre client.']
                ]
            ], 422);
        }

        $client = Client::create([
            'nom_complet' => $request->nom_complet,
            'telephone'   => $telephoneClean,
            'adresse'     => $request->adresse,
            'boutique_id' => $boutiqueId,
        ]);

        return response()->json(['message' => 'Client créé avec succès', 'client' => $client], 201);
    }

    public function show(Client $client)
    {
        $client->load('boutique', 'commandes');

        $commandes = $client->commandes;
        $totalCommandes = $commandes->count();
        $commandesValidees = $commandes->where('statut', 'validee');
        $commandesEnCours = $commandes->where('statut', 'en_cours');
        $commandesAnnulees = $commandes->where('statut', 'annulee');

        $totalDepense = $commandesValidees->sum(function ($cmd) {
            return (float) $cmd->total;
        });

        $commandeMoyenne = $commandesValidees->count() > 0
            ? round($totalDepense / $commandesValidees->count(), 2)
            : 0;

        $derniereCommande = $commandesValidees
            ->sortByDesc('date_validation')
            ->first();

        return response()->json([
            'client' => [
                'id' => $client->id,
                'nom_complet' => $client->nom_complet,
                'telephone' => $client->telephone,
                'email' => $client->email,
                'adresse' => $client->adresse,
                'actif' => $client->actif,
                'boutique_id' => $client->boutique_id,
                'created_at' => $client->created_at,
                'updated_at' => $client->updated_at,
                'boutique' => $client->boutique,
            ],
            'statistiques' => [
                'total_commandes' => $totalCommandes,
                'total_depense' => $totalDepense,
                'commande_moyenne' => $commandeMoyenne,
                'derniere_commande' => $derniereCommande ? $derniereCommande->date_validation : null,
                'commandes_validees' => $commandesValidees->count(),
                'commandes_en_cours' => $commandesEnCours->count(),
                'commandes_annulees' => $commandesAnnulees->count(),
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
                ];
            })->values()->all(),
        ]);
    }

    public function update(Request $request, Client $client)
    {
        $user = $request->user();

        if (!$user->isAdmin() && (int) $client->boutique_id !== (int) $user->boutique_id) {
            return response()->json(['message' => 'Vous ne pouvez pas modifier un client d\'une autre boutique.'], 403);
        }

        $request->validate([
            'nom_complet' => 'sometimes|string|max:255',
            'telephone'   => 'sometimes|string|max:20',
            'adresse'     => 'nullable|string',
        ]);

        if ($request->has('telephone')) {
            $telephoneClean = $this->normalizePhone($request->telephone);

            // Vérification globale : le numéro existe déjà (sauf ce client)
            $exists = Client::where('telephone', $telephoneClean)
                ->where('id', '!=', $client->id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'message' => 'Ce numéro de téléphone existe déjà.',
                    'errors' => [
                        'telephone' => ['Ce numéro est déjà utilisé par un autre client.']
                    ]
                ], 422);
            }

            $client->telephone = $telephoneClean;
        }

        if ($request->has('nom_complet')) {
            $client->nom_complet = $request->nom_complet;
        }

        if ($request->has('adresse')) {
            $client->adresse = $request->adresse;
        }

        $client->save();

        return response()->json(['message' => 'Client modifié avec succès', 'client' => $client]);
    }

    public function destroy(Request $request, Client $client)
    {
        $user = $request->user();

        if (!$user->isAdmin() && (int) $client->boutique_id !== (int) $user->boutique_id) {
            return response()->json(['message' => 'Vous ne pouvez pas supprimer un client d\'une autre boutique.'], 403);
        }

        if ($client->commandes()->count() > 0) {
            return response()->json(['message' => 'Impossible de supprimer : ce client a des commandes'], 400);
        }

        $client->delete();

        return response()->json(['message' => 'Client supprimé avec succès']);
    }

    /**
     * Normalise un numéro de téléphone :
     * - Supprime espaces, tirets, parenthèses, +, 00 en début
     * - Numéro sénégalais 9 chiffres (7XXXXXXXX) → ajoute 221
     * - Numéro sénégalais 8 chiffres (7XXXXXXX) → ajoute 221
     * - 00221... → 221...
     * - +221... → 221...
     * - Déjà 221XXXXXXXXX → OK
     * - International → laisse tel quel
     */
    private function normalizePhone(string $phone): string
    {
        // Supprimer espaces, tirets, parenthèses, points
        $phone = preg_replace('/[\s\-\(\)\.]/', '', $phone);

        // Supprimer le + au début
        $phone = ltrim($phone, '+');

        // Supprimer 00 au début (format international 00221...)
        if (str_starts_with($phone, '00')) {
            $phone = substr($phone, 2);
        }

        // Numéro sénégalais 9 chiffres (7XXXXXXXX) → ajouter 221
        if (preg_match('/^7[0-9]{8}$/', $phone)) {
            $phone = '221' . $phone;
        }

        // Numéro sénégalais 8 chiffres (7XXXXXXX) → ajouter 221
        if (preg_match('/^7[0-9]{7}$/', $phone)) {
            $phone = '221' . $phone;
        }

        return $phone;
    }
}
