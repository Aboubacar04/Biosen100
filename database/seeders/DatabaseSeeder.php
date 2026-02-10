<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        echo "\n🚀 Début du seeding...\n\n";

        // 1. Admin
        echo "📝 Étape 1/10 : Admin\n";
        $this->call(AdminSeeder::class);

        // 2. Boutiques
        echo "\n📝 Étape 2/10 : Boutiques\n";
        $this->call(BoutiqueSeeder::class);

        // 3. Gérants (NOUVEAU)
        echo "\n📝 Étape 3/10 : Gérants\n";
        $this->call(GerantSeeder::class);

        // 4. Catégories
        echo "\n📝 Étape 4/10 : Catégories\n";
        $this->call(CategorieSeeder::class);

        // 5. Produits
        echo "\n📝 Étape 5/10 : Produits\n";
        $this->call(ProduitSeeder::class);

        // 6. Employés
        echo "\n📝 Étape 6/10 : Employés\n";
        $this->call(EmployeSeeder::class);

        // 7. Livreurs
        echo "\n📝 Étape 7/10 : Livreurs\n";
        $this->call(LivreurSeeder::class);

        // 8. Clients
        echo "\n📝 Étape 8/10 : Clients\n";
        $this->call(ClientSeeder::class);

        // 9. Commandes
        echo "\n📝 Étape 9/10 : Commandes\n";
        $this->call(CommandeSeeder::class);

        // 10. Dépenses
        echo "\n📝 Étape 10/10 : Dépenses\n";
        $this->call(DepenseSeeder::class);

        echo "\n\n✅ SEEDING TERMINÉ AVEC SUCCÈS !\n";
        echo "🎉 Base de données remplie avec des données de test\n\n";
    }
}
