<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Boutique;

class BoutiqueSeeder extends Seeder
{
    public function run(): void
    {
        $boutiques = [
            [
                'nom' => 'Boutique Centre-Ville',
                'adresse' => 'Avenue Lamine Gueye, Dakar',
                'telephone' => '221338234567',
                'actif' => true,
            ],
            [
                'nom' => 'Boutique Plateau',
                'adresse' => 'Rue Mohamed V, Plateau, Dakar',
                'telephone' => '221338345678',
                'actif' => true,
            ],
            [
                'nom' => 'Boutique Parcelles',
                'adresse' => 'Unité 15, Parcelles Assainies',
                'telephone' => '221338456789',
                'actif' => true,
            ],
            [
                'nom' => 'Boutique Grand-Yoff',
                'adresse' => 'Cité Assemblée, Grand-Yoff',
                'telephone' => '221338567890',
                'actif' => false, // Une boutique désactivée pour test
            ],
        ];

        foreach ($boutiques as $boutique) {
            Boutique::create($boutique);
        }

        echo "✅ 4 boutiques créées\n";
    }
}
