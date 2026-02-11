<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    protected $imageService;

    public function __construct(ImageService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * 📋 LISTE tous les utilisateurs
     * Route : GET /api/users
     */
    public function index(Request $request)
    {
        $users = User::with('boutique')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($users);
    }

    /**
     * ➕ CRÉER un utilisateur (admin ou gérant)
     * Route : POST /api/users
     */
    public function store(Request $request)
    {
        $request->validate([
            'nom'         => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:8',
            'role'        => 'required|in:admin,gerant',
            'boutique_id' => 'nullable|exists:boutiques,id',
            'photo'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'actif'       => 'sometimes|boolean',
        ]);

        // 🔒 Un gérant DOIT avoir une boutique
        if ($request->role === 'gerant' && !$request->boutique_id) {
            return response()->json([
                'message' => 'Un gérant doit être assigné à une boutique'
            ], 422);
        }

        // 🔒 Un admin n'a pas de boutique
        if ($request->role === 'admin') {
            $request->merge(['boutique_id' => null]);
        }

        $data = [
            'nom'         => $request->nom,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'role'        => $request->role,
            'boutique_id' => $request->boutique_id,
            'actif'       => $request->input('actif', true),
        ];

        if ($request->hasFile('photo')) {
            $data['photo'] = $this->imageService->upload($request->file('photo'), 'users');
        }

        $user = User::create($data);

        return response()->json([
            'message' => 'Utilisateur créé avec succès',
            'user'    => $user->load('boutique')
        ], 201);
    }

    /**
     * 👁️ AFFICHER un utilisateur
     * Route : GET /api/users/{id}
     */
    public function show(User $user)
    {
        return response()->json($user->load('boutique'));
    }

    /**
     * ✏️ MODIFIER un utilisateur
     * Route : PUT /api/users/{id}
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'nom'         => 'sometimes|string|max:255',
            'email'       => 'sometimes|email|unique:users,email,' . $user->id,
            'password'    => 'nullable|string|min:8',
            'role'        => 'sometimes|in:admin,gerant',
            'boutique_id' => 'nullable|exists:boutiques,id',
            'photo'       => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'actif'       => 'sometimes|boolean',
        ]);

        // 🔒 Vérification rôle/boutique
        $newRole = $request->input('role', $user->role);

        if ($newRole === 'gerant') {
            $newBoutiqueId = $request->input('boutique_id', $user->boutique_id);
            if (!$newBoutiqueId) {
                return response()->json([
                    'message' => 'Un gérant doit être assigné à une boutique'
                ], 422);
            }
        }

        if ($newRole === 'admin') {
            $request->merge(['boutique_id' => null]);
        }

        $data = $request->only(['nom', 'email', 'role', 'boutique_id', 'actif']);

        // 🔑 Modifier le mot de passe si fourni
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        // 📸 Modifier la photo si fournie
        if ($request->hasFile('photo')) {
            $data['photo'] = $this->imageService->update(
                $request->file('photo'),
                $user->photo,
                'users'
            );
        }

        $user->update($data);

        return response()->json([
            'message' => 'Utilisateur modifié avec succès',
            'user'    => $user->load('boutique')
        ]);
    }

    /**
     * 🔄 CHANGER le rôle uniquement
     * Route : PATCH /api/users/{id}/role
     */
    public function changerRole(Request $request, User $user)
    {
        $request->validate([
            'role'        => 'required|in:admin,gerant',
            'boutique_id' => 'nullable|exists:boutiques,id',
        ]);

        // 🔒 Gérant doit avoir boutique
        if ($request->role === 'gerant' && !$request->boutique_id && !$user->boutique_id) {
            return response()->json([
                'message' => 'Un gérant doit être assigné à une boutique'
            ], 422);
        }

        $user->update([
            'role'        => $request->role,
            'boutique_id' => $request->role === 'admin' ? null : ($request->boutique_id ?? $user->boutique_id),
        ]);

        return response()->json([
            'message' => 'Rôle modifié avec succès',
            'user'    => $user->load('boutique')
        ]);
    }

    /**
     * 🔄 ACTIVER / DÉSACTIVER un utilisateur
     * Route : PATCH /api/users/{id}/toggle-actif
     */
    public function toggleActif(User $user)
    {
        // 🔒 Ne pas désactiver son propre compte
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Vous ne pouvez pas désactiver votre propre compte'
            ], 403);
        }

        $user->update(['actif' => !$user->actif]);

        return response()->json([
            'message' => $user->actif ? 'Utilisateur activé' : 'Utilisateur désactivé',
            'user'    => $user->load('boutique')
        ]);
    }

    /**
     * 🗑️ SUPPRIMER un utilisateur
     * Route : DELETE /api/users/{id}
     */
    public function destroy(User $user)
    {
        // 🔒 Ne pas supprimer son propre compte
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'Vous ne pouvez pas supprimer votre propre compte'
            ], 403);
        }

        $this->imageService->delete($user->photo);
        $user->delete();

        return response()->json([
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }
}
