<?php
namespace Database\Seeders;
use App\Models\Livreur;
use App\Models\Boutique;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class LivreurSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $boutique = Boutique::first();
        
        for ($i = 0; $i < 10; $i++) {
            Livreur::create([
                'boutique_id' => $boutique->id,
                'nom' => $faker->name(),
                'telephone' => $faker->phoneNumber(),
                'disponible' => 1,
                'actif' => 1,
            ]);
        }
    }
}