<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BoutiqueController;
use App\Http\Controllers\Api\CategorieController;
use App\Http\Controllers\Api\ProduitController;
use App\Http\Controllers\Api\EmployeController;
use App\Http\Controllers\Api\LivreurController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\CommandeController;
use App\Http\Controllers\Api\FactureController;
use App\Http\Controllers\Api\DepenseController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\GammeController;
use App\Http\Controllers\Api\CommandeBisenController;

/*
|--------------------------------------------------------------------------
| API Routes - Biosen100
|--------------------------------------------------------------------------
*/

// ========================================
// 🔓 ROUTES PUBLIQUES (Sans authentification)
// ========================================

Route::prefix('auth')->group(function () {
    Route::post('/login',          [AuthController::class, 'login']);
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('/reset-password',  [AuthController::class, 'resetPassword']);
});

// ========================================
// 👤 GESTION UTILISATEURS (ADMIN SEULEMENT)
// ========================================

Route::middleware(['auth:sanctum', 'account.active', 'admin'])->prefix('users')->group(function () {
    Route::get('/',                      [UserController::class, 'index']);
    Route::post('/',                     [UserController::class, 'store']);
    Route::get('/{user}',                [UserController::class, 'show']);
    Route::post('/{user}',              [UserController::class, 'update']);
    Route::patch('/{user}/role',         [UserController::class, 'changerRole']);
    Route::patch('/{user}/toggle-actif', [UserController::class, 'toggleActif']);
    Route::delete('/{user}',             [UserController::class, 'destroy']);
});

// ========================================
// 👤 AUTH (tous les utilisateurs connectés, y compris saisisseur)
// ========================================

Route::middleware(['auth:sanctum', 'account.active'])->prefix('auth')->group(function () {
    Route::post('/logout',           [AuthController::class, 'logout']);
    Route::get('/me',                [AuthController::class, 'me']);
    Route::post('/change-password',  [AuthController::class, 'changePassword']);
});

// ========================================
// 📝 SAISIES COMMANDES (pas besoin de boutique)
// ========================================

Route::middleware(['auth:sanctum', 'account.active'])->group(function () {
    Route::post('/commande-bisens',                    [CommandeBisenController::class, 'store']);
    Route::get('/commande-bisens',                     [CommandeBisenController::class, 'index']);
    Route::delete('/commande-bisens/{commandeBisen}',  [CommandeBisenController::class, 'destroy']);
});

// ========================================
// 🔐 ROUTES PROTÉGÉES (avec boutique active)
// ========================================

Route::middleware(['auth:sanctum', 'account.active', 'boutique.active'])->group(function () {

    // ----------------------------------------
    // 🏪 BOUTIQUES (Admin uniquement)
    // ----------------------------------------
    Route::middleware('admin')->prefix('boutiques')->group(function () {
        Route::get('/',                          [BoutiqueController::class, 'index']);
        Route::post('/',                         [BoutiqueController::class, 'store']);
        Route::get('/{boutique}',                [BoutiqueController::class, 'show']);
        Route::put('/{boutique}',                [BoutiqueController::class, 'update']);
        Route::delete('/{boutique}',             [BoutiqueController::class, 'destroy']);
        Route::post('/{boutique}/toggle-status', [BoutiqueController::class, 'toggleStatus']);
    });

    // ----------------------------------------
    // 📂 CATÉGORIES
    // ----------------------------------------
    Route::prefix('categories')->group(function () {
        Route::get('/',              [CategorieController::class, 'index']);
        Route::post('/',             [CategorieController::class, 'store']);
        Route::get('/{categorie}',   [CategorieController::class, 'show']);
        Route::put('/{categorie}',   [CategorieController::class, 'update']);
        Route::delete('/{categorie}',[CategorieController::class, 'destroy']);
    });

    // ----------------------------------------
    // 📦 PRODUITS
    // ----------------------------------------
    Route::prefix('produits')->group(function () {
        Route::get('/',              [ProduitController::class, 'index']);
        Route::get('/stock-faible',  [ProduitController::class, 'stockFaible']);
        Route::post('/',             [ProduitController::class, 'store']);
        Route::get('/{produit}',     [ProduitController::class, 'show']);
        Route::post('/{produit}',    [ProduitController::class, 'update']);
        Route::delete('/{produit}',  [ProduitController::class, 'destroy']);
    });

    // ----------------------------------------
    // 👷 EMPLOYÉS
    // ----------------------------------------
    Route::prefix('employes')->group(function () {
        Route::get('/',              [EmployeController::class, 'index']);
        Route::post('/',             [EmployeController::class, 'store']);
        Route::get('/{employe}',     [EmployeController::class, 'show']);
        Route::post('/{employe}',    [EmployeController::class, 'update']);
        Route::delete('/{employe}',  [EmployeController::class, 'destroy']);
    });

    // ----------------------------------------
    // 🚚 LIVREURS
    // ----------------------------------------
    Route::prefix('livreurs')->group(function () {
        Route::get('/',                                  [LivreurController::class, 'index']);
        Route::get('/disponibles',                       [LivreurController::class, 'disponibles']);
        Route::post('/',                                 [LivreurController::class, 'store']);
        Route::get('/{livreur}',                         [LivreurController::class, 'show']);
        Route::put('/{livreur}',                         [LivreurController::class, 'update']);
        Route::delete('/{livreur}',                      [LivreurController::class, 'destroy']);
        Route::post('/{livreur}/toggle-disponibilite',   [LivreurController::class, 'toggleDisponibilite']);
    });

    // ----------------------------------------
    // 👥 CLIENTS
    // ----------------------------------------
    Route::prefix('clients')->group(function () {
        Route::get('/',                      [ClientController::class, 'index']);
        Route::get('/autocomplete',          [ClientController::class, 'autocomplete']);
        Route::get('/search',                [ClientController::class, 'search']);
        Route::get('/recherche-telephone',   [ClientController::class, 'rechercherParTelephone']);
        Route::post('/',                     [ClientController::class, 'store']);
        Route::get('/{client}',              [ClientController::class, 'show']);
        Route::put('/{client}',              [ClientController::class, 'update']);
        Route::delete('/{client}',           [ClientController::class, 'destroy']);
    });

    // ----------------------------------------
    // 🛒 COMMANDES
    // ----------------------------------------
    Route::prefix('commandes')->group(function () {
        // Routes statiques
        Route::get('/en-cours',   [CommandeController::class, 'enCours']);
        Route::get('/validees',   [CommandeController::class, 'validees']);
        Route::get('/annulees',   [CommandeController::class, 'annulees']);
        Route::get('/historique', [CommandeController::class, 'historique']);
        Route::get('/employe/{employe_id}/du-jour', [CommandeController::class, 'commandesDuJourEmploye']);
        Route::get('/search',     [CommandeController::class, 'search']);

        // CRUD
        Route::get('/',    [CommandeController::class, 'index']);
        Route::post('/',   [CommandeController::class, 'store']);

        // Routes dynamiques
        Route::get('/{commande}',          [CommandeController::class, 'show']);
        Route::put('/{commande}',          [CommandeController::class, 'update']);
        Route::delete('/{commande}',       [CommandeController::class, 'destroy']);
        Route::post('/{commande}/valider', [CommandeController::class, 'valider']);
        Route::post('/{commande}/annuler', [CommandeController::class, 'annuler']);
    });

    // ----------------------------------------
    // 📦 GAMMES
    // ----------------------------------------
    Route::prefix('gammes')->group(function () {
        Route::get('/',           [GammeController::class, 'index']);
        Route::post('/',          [GammeController::class, 'store']);
        Route::get('/{gamme}',    [GammeController::class, 'show']);
        Route::put('/{gamme}',    [GammeController::class, 'update']);
        Route::delete('/{gamme}', [GammeController::class, 'destroy']);
    });

    // ----------------------------------------
    // 🧾 FACTURES
    // ----------------------------------------
    Route::prefix('factures')->group(function () {
        Route::get('/aujourdhui',  [FactureController::class, 'aujourdhui']);
        Route::get('/semaine',     [FactureController::class, 'semaine']);
        Route::get('/mois',        [FactureController::class, 'mois']);
        Route::get('/annee',       [FactureController::class, 'annee']);
        Route::get('/search',      [FactureController::class, 'search']);
        Route::get('/',            [FactureController::class, 'index']);
        Route::get('/{facture}',   [FactureController::class, 'show']);
    });

    Route::get('/commandes/{commande}/facture', [FactureController::class, 'parCommande']);

    // ----------------------------------------
    // 💰 DÉPENSES
    // ----------------------------------------
    Route::prefix('depenses')->group(function () {
        Route::get('/',             [DepenseController::class, 'index']);
        Route::get('/par-date',     [DepenseController::class, 'parDate']);
        Route::post('/',            [DepenseController::class, 'store']);
        Route::get('/{depense}',    [DepenseController::class, 'show']);
        Route::put('/{depense}',    [DepenseController::class, 'update']);
        Route::delete('/{depense}', [DepenseController::class, 'destroy']);
    });

    // ----------------------------------------
    // 📊 DASHBOARD / STATISTIQUES
    // ----------------------------------------
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats',              [DashboardController::class, 'stats']);
        Route::get('/top-produits',       [DashboardController::class, 'topProduits']);
        Route::get('/top-employes',       [DashboardController::class, 'topEmployes']);
        Route::get('/top-livreurs',       [DashboardController::class, 'topLivreurs']);
        Route::get('/commandes-semaine',  [DashboardController::class, 'commandesSemaine']);
        Route::get('/commandes-mois',     [DashboardController::class, 'commandesMois']);
        Route::get('/evolution-ventes',   [DashboardController::class, 'evolutionVentes']);
        Route::get('/stock-faible',               [DashboardController::class, 'stockFaible']);
        Route::get('/stats-employe/{employe}',    [DashboardController::class, 'statsEmploye']);
    });

}); // ← FIN du groupe boutique.active