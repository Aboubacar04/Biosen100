<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Produit;
use App\Models\Categorie;
use Faker\Factory as Faker;

class ProduitSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        $produits = [
            // Alimentation
            ['nom' => 'Riz Brisé 50kg', 'prix' => 25000, 'categorie' => 'Alimentation', 'stock' => 50],
            ['nom' => 'Riz Parfumé 25kg', 'prix' => 15000, 'categorie' => 'Alimentation', 'stock' => 30],
            ['nom' => 'Huile Végétale 5L', 'prix' => 7500, 'categorie' => 'Alimentation', 'stock' => 80],
            ['nom' => 'Sucre 1kg', 'prix' => 750, 'categorie' => 'Alimentation', 'stock' => 100],
            ['nom' => 'Farine 1kg', 'prix' => 500, 'categorie' => 'Alimentation', 'stock' => 60],
            ['nom' => 'Pâtes Alimentaires 500g', 'prix' => 350, 'categorie' => 'Alimentation', 'stock' => 120],

            // Boissons
            ['nom' => 'Eau Minérale 1.5L', 'prix' => 300, 'categorie' => 'Boissons', 'stock' => 200],
            ['nom' => 'Coca-Cola 1.5L', 'prix' => 1000, 'categorie' => 'Boissons', 'stock' => 150],
            ['nom' => 'Jus d\'Orange 1L', 'prix' => 1500, 'categorie' => 'Boissons', 'stock' => 80],
            ['nom' => 'Bissap Concentré 1L', 'prix' => 2000, 'categorie' => 'Boissons', 'stock' => 40],

            // Hygiène
            ['nom' => 'Savon de Marseille', 'prix' => 500, 'categorie' => 'Hygiène', 'stock' => 100],
            ['nom' => 'Dentifrice Signal', 'prix' => 1200, 'categorie' => 'Hygiène', 'stock' => 60],
            ['nom' => 'Shampoing 500ml', 'prix' => 2500, 'categorie' => 'Hygiène', 'stock' => 45],
            ['nom' => 'Gel Douche 750ml', 'prix' => 3000, 'categorie' => 'Hygiène', 'stock' => 50],

            // Entretien
            ['nom' => 'Javel 2L', 'prix' => 800, 'categorie' => 'Entretien', 'stock' => 70],
            ['nom' => 'Liquide Vaisselle 1L', 'prix' => 1500, 'categorie' => 'Entretien', 'stock' => 55],
            ['nom' => 'Détergent en Poudre 2kg', 'prix' => 2000, 'categorie' => 'Entretien', 'stock' => 40],

            // Épicerie
            ['nom' => 'Café Touba 250g', 'prix' => 1500, 'categorie' => 'Épicerie', 'stock' => 90],
            ['nom' => 'Thé Lipton (boîte)', 'prix' => 2500, 'categorie' => 'Épicerie', 'stock' => 70],
            ['nom' => 'Lait en Poudre 500g', 'prix' => 3500, 'categorie' => 'Épicerie', 'stock' => 35],

            // Produits Laitiers
            ['nom' => 'Yaourt Nature (pack 4)', 'prix' => 1200, 'categorie' => 'Produits Laitiers', 'stock' => 60],
            ['nom' => 'Lait Frais 1L', 'prix' => 1000, 'categorie' => 'Produits Laitiers', 'stock' => 80],
            ['nom' => 'Fromage Vache Qui Rit', 'prix' => 2000, 'categorie' => 'Produits Laitiers', 'stock' => 45],

            // Fruits & Légumes
            ['nom' => 'Tomates (1kg)', 'prix' => 800, 'categorie' => 'Fruits & Légumes', 'stock' => 50],
            ['nom' => 'Oignons (1kg)', 'prix' => 600, 'categorie' => 'Fruits & Légumes', 'stock' => 70],
            ['nom' => 'Pommes de terre (1kg)', 'prix' => 500, 'categorie' => 'Fruits & Légumes', 'stock' => 100],
            ['nom' => 'Bananes (1kg)', 'prix' => 1000, 'categorie' => 'Fruits & Légumes', 'stock' => 60],
            ['nom' => 'Oranges (1kg)', 'prix' => 1200, 'categorie' => 'Fruits & Légumes', 'stock' => 55],
        ];

        $categories = Categorie::all();
        $count = 0;

        foreach ($categories as $categorie) {
            // Filtrer les produits de cette catégorie
            $produitsCategorie = array_filter($produits, function($p) use ($categorie) {
                return $p['categorie'] === $categorie->nom;
            });

            foreach ($produitsCategorie as $produitData) {
                Produit::create([
                    'nom' => $produitData['nom'],
                    'description' => 'Produit de qualité - ' . $produitData['nom'],
                    'prix_vente' => $produitData['prix'],
                    'stock' => $produitData['stock'],
                    'seuil_alerte' => $faker->numberBetween(5, 20),
                    'categorie_id' => $categorie->id,
                    'boutique_id' => $categorie->boutique_id,
                    'actif' => true,
                ]);
                $count++;
            }
        }

        echo "✅ $count produits créés\n";
    }
}
