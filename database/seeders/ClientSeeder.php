<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Boutique;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');
        $boutiques = Boutique::all();

        $this->command->info('Creation de 50 clients...');

        for ($i = 0; $i < 50; $i++) {
            Client::create([
                'nom_complet' => $faker->name(),
                'telephone' => $faker->phoneNumber(),
                'adresse' => $faker->address(),
                'boutique_id' => $boutiques->random()->id,
            ]);

            if (($i + 1) % 10 === 0) {
                $this->command->info('[OK] ' . ($i + 1) . '/50 clients crees');
            }
        }

        $this->command->info('50 clients crees avec succes!');
    }
}