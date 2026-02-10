<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Livreur;
use App\Models\Boutique;
use Faker\Factory as Faker;

class LivreurSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        $prenoms = ['Modou', 'Demba', 'Pape', 'Alioune', 'Babacar', 'Youssou', 'Serigne'];
        $noms = ['Diagne', 'Mbaye', 'Toure', 'Wade', 'Ndao', 'Sane', 'Seye'];

        $boutiques = Boutique::where('actif', true)->get();
        $count = 0;

        foreach ($boutiques as $boutique) {
            // 3-5 livreurs par boutique
            $nombreLivreurs = $faker->numberBetween(3, 5);

            for ($i = 0; $i < $nombreLivreurs; $i++) {
                Livreur::create([
                    'nom' => $prenoms[array_rand($prenoms)] . ' ' . $noms[array_rand($noms)],
                    'telephone' => '221' . $faker->numerify('7########'),
                    'disponible' => $faker->boolean(80), // 80% disponibles
                    'boutique_id' => $boutique->id,
                    'actif' => true,
                ]);
                $count++;
            }
        }

        echo "✅ $count livreurs créés\n";
    }
}
