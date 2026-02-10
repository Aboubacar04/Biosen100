<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Depense;
use App\Models\Boutique;
use Faker\Factory as Faker;
use Carbon\Carbon;

class DepenseSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        $categories = [
            'Électricité',
            'Eau',
            'Loyer',
            'Salaires',
            'Transport',
            'Entretien',
            'Fournitures',
            'Téléphone/Internet',
        ];

        $boutiques = Boutique::where('actif', true)->get();
        $count = 0;

        foreach ($boutiques as $boutique) {
            // Créer des dépenses sur les 30 derniers jours
            for ($jour = 30; $jour >= 0; $jour--) {
                $date = Carbon::now()->subDays($jour);

                // 1-3 dépenses par jour
                $nombreDepenses = $faker->numberBetween(1, 3);

                for ($i = 0; $i < $nombreDepenses; $i++) {
                    Depense::create([
                        'boutique_id' => $boutique->id,
                        'description' => $faker->sentence(),
                        'montant' => $faker->numberBetween(5000, 100000),
                        'categorie' => $categories[array_rand($categories)],
                        'date_depense' => $date,
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);
                    $count++;
                }
            }
        }

        echo "✅ $count dépenses créées\n";
    }
}
