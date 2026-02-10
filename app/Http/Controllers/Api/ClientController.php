<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * 📋 LISTE tous les clients
     */
    public function index(Request $request)
    {
        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Client::withCount('commandes');

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $clients = $query->orderBy('nom_complet')->get();

        return response()->json($clients);
    }

    /**
     * ➕ CRÉER un client
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom_complet' => 'required|string|max:255',
            'telephone' => 'required|string|max:20',
            'adresse' => 'nullable|string',
            'boutique_id' => 'required|exists:boutiques,id',
        ]);

        $client = Client::create($request->only(['nom_complet', 'telephone', 'adresse', 'boutique_id']));

        return response()->json([
            'message' => 'Client créé avec succès',
            'client' => $client
        ], 201);
    }

    /**
     * 👁️ AFFICHER un client
     */
    public function show(Client $client)
    {
        $client->load('boutique', 'commandes');
        return response()->json($client);
    }

    /**
     * ✏️ MODIFIER un client
     */
    public function update(Request $request, Client $client)
    {
        $request->validate([
            'nom_complet' => 'sometimes|string|max:255',
            'telephone' => 'sometimes|string|max:20',
            'adresse' => 'nullable|string',
        ]);

        $client->update($request->only(['nom_complet', 'telephone', 'adresse']));

        return response()->json([
            'message' => 'Client modifié avec succès',
            'client' => $client
        ]);
    }

    /**
     * 🗑️ SUPPRIMER un client
     */
    public function destroy(Client $client)
    {
        if ($client->commandes()->count() > 0) {
            return response()->json([
                'message' => 'Impossible de supprimer : ce client a des commandes'
            ], 400);
        }

        $client->delete();

        return response()->json([
            'message' => 'Client supprimé avec succès'
        ]);
    }

    /**
     * 🔍 RECHERCHER un client par téléphone
     */
    public function search(Request $request)
    {
        $request->validate(['telephone' => 'required|string']);

        $boutiqueId = $request->user()->isAdmin()
            ? $request->input('boutique_id')
            : $request->user()->boutique_id;

        $query = Client::where('telephone', $request->telephone);

        if ($boutiqueId) {
            $query->where('boutique_id', $boutiqueId);
        }

        $client = $query->first();

        if (!$client) {
            return response()->json(['message' => 'Client non trouvé'], 404);
        }

        return response()->json($client);
    }
}
