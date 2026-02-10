<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Employe;
use App\Models\Boutique;
use Faker\Factory as Faker;

class EmployeSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        $prenoms = [
            'Mamadou', 'Fatou', 'Ousmane', 'Awa', 'Cheikh',
            'Aissatou', 'Ibrahima', 'Marieme', 'Moussa', 'Khady',
            'Abdoulaye', 'Ndeye', 'Malick', 'Aminata', 'Samba'
        ];

        $noms = [
            'Diallo', 'Sow', 'Ba', 'Ndiaye', 'Diop',
            'Fall', 'Gueye', 'Sarr', 'Sy', 'Seck',
            'Faye', 'Kane', 'Cisse', 'Diouf', 'Thiam'
        ];

        $boutiques = Boutique::where('actif', true)->get();
        $count = 0;

        foreach ($boutiques as $boutique) {
            // 5-8 employés par boutique
            $nombreEmployes = $faker->numberBetween(5, 8);

            for ($i = 0; $i < $nombreEmployes; $i++) {
                Employe::create([
                    'nom' => $prenoms[array_rand($prenoms)] . ' ' . $noms[array_rand($noms)],
                    'telephone' => '221' . $faker->numerify('7########'),
                    'boutique_id' => $boutique->id,
                    'actif' => true,
                ]);
                $count++;
            }
        }

        echo "✅ $count employés créés\n";
    }
}
