<?php
namespace Database\Seeders;
use App\Models\Employe;
use App\Models\Boutique;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class EmployeSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $boutique = Boutique::first();
        
        for ($i = 0; $i < 20; $i++) {
            Employe::create([
                'boutique_id' => $boutique->id,
                'nom' => $faker->name(),
                'telephone' => $faker->phoneNumber(),
                'actif' => 1,
            ]);
        }
    }
}