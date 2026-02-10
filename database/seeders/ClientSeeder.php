<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Client;
use App\Models\Boutique;
use Faker\Factory as Faker;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        $boutiques = Boutique::where('actif', true)->get();
        $count = 0;

        foreach ($boutiques as $boutique) {
            // 20-30 clients par boutique
            $nombreClients = $faker->numberBetween(20, 30);

            for ($i = 0; $i < $nombreClients; $i++) {
                Client::create([
                    'nom_complet' => $faker->name(),
                    'telephone' => '221' . $faker->numerify('7########'),
                    'adresse' => $faker->address(),
                    'boutique_id' => $boutique->id,
                ]);
                $count++;
            }
        }

        echo "✅ $count clients créés\n";
    }
}
