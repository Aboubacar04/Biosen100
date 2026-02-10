<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Categorie;
use App\Models\Boutique;

class CategorieSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Alimentation',
            'Boissons',
            'Hygiène',
            'Entretien',
            'Épicerie',
            'Produits Laitiers',
            'Fruits & Légumes',
        ];

        // Pour chaque boutique active
        $boutiques = Boutique::where('actif', true)->get();

        foreach ($boutiques as $boutique) {
            foreach ($categories as $nom) {
                Categorie::create([
                    'nom' => $nom,
                    'description' => 'Catégorie ' . $nom . ' de ' . $boutique->nom,
                    'boutique_id' => $boutique->id,
                ]);
            }
        }

        echo "✅ " . (count($categories) * $boutiques->count()) . " catégories créées\n";
    }
}
