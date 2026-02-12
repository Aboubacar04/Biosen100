<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * Liste des clients avec recherche
     * GET /api/clients?search=&boutique_id=&per_page=
     */
    public function index(Request $request)
    {
        $search = $request->input('search', '');
        $perPage = $request->input('per_page', 15);
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Client::withCount('commandes');

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nom_complet', 'like', '%' . $search . '%')
                    ->orWhere('telephone', 'like', '%' . $search . '%');
            });
        }

        return response()->json($query->latest()->paginate($perPage));
    }

    /**
     * Autocomplete pour commande-create
     * GET /api/clients/autocomplete?q=&boutique_id=
     */
    public function autocomplete(Request $request)
    {
        $q = $request->input('q', '');
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $query = Client::query();

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $clients = $query->where(function ($query) use ($q) {
            $query->where('nom_complet', 'like', '%' . $q . '%')
                ->orWhere('telephone', 'like', '%' . $q . '%');
        })
            ->limit(10)
            ->get(['id', 'nom_complet', 'telephone', 'adresse']);

        return response()->json($clients);
    }

    /**
     * Recherche client par téléphone
     * GET /api/clients/search?telephone=
     */
    public function search(Request $request)
    {
        $request->validate(['telephone' => 'required|string']);

        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $telephone = $this->cleanPhoneNumber($request->telephone);

        $query = Client::where('telephone', $telephone);

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $client = $query->first();

        if (!$client) {
            return response()->json(['message' => 'Client non trouvé'], 404);
        }

        return response()->json($client);
    }

    /**
     * Recherche par téléphone (pour commande-create)
     * GET /api/clients/recherche-telephone?telephone=
     */
    public function rechercherParTelephone(Request $request)
    {
        $telephone = $request->input('telephone');
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        if (!$telephone) {
            return response()->json(['message' => 'Numéro de téléphone requis'], 400);
        }

        $telephoneClean = $this->cleanPhoneNumber($telephone);

        $query = Client::query();

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $client = $query->where('telephone', $telephoneClean)->first();

        if (!$client) {
            return response()->json(['message' => 'Client non trouvé'], 404);
        }

        return response()->json($client);
    }

    /**
     * Créer un client
     * POST /api/clients
     */
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

        // Nettoyer le téléphone
        $telephoneClean = $this->cleanPhoneNumber($request->telephone);

        // ⚠️ VÉRIFICATION UNICITÉ TÉLÉPHONE
        $exists = Client::where('telephone', $telephoneClean)->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Ce numéro de téléphone existe déjà.',
                'errors' => [
                    'telephone' => ['Ce numéro de téléphone est déjà utilisé. Veuillez utiliser un autre numéro.']
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

    /**
     * Afficher un client
     * GET /api/clients/{id}
     */
    public function show(Client $client)
    {
        $client->load('boutique', 'commandes');
        return response()->json($client);
    }

    /**
     * Modifier un client
     * PUT /api/clients/{id}
     */
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

        // Si téléphone modifié, vérifier unicité
        if ($request->has('telephone')) {
            $telephoneClean = $this->cleanPhoneNumber($request->telephone);

            // Vérifier que ce numéro n'existe pas (sauf pour ce client)
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

    /**
     * Supprimer un client
     * DELETE /api/clients/{id}
     */
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
     * Nettoyer le numéro de téléphone
     * Enlève espaces, tirets, parenthèses
     */
    private function cleanPhoneNumber(string $phone): string
    {
        return preg_replace('/[\s\-\(\)]/', '', $phone);
    }
}
