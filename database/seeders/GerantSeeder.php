<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Boutique;
use Illuminate\Support\Facades\Hash;

class GerantSeeder extends Seeder
{
    public function run(): void
    {
        $boutiques = Boutique::where('actif', true)->get();

        $gerants = [
            [
                'nom' => 'Mamadou Diallo',
                'email' => 'mamadou@biosen.sn',
                'password' => 'password123',
            ],
            [
                'nom' => 'Fatou Sow',
                'email' => 'fatou@biosen.sn',
                'password' => 'password123',
            ],
            [
                'nom' => 'Ousmane Ba',
                'email' => 'ousmane@biosen.sn',
                'password' => 'password123',
            ],
        ];

        foreach ($boutiques as $index => $boutique) {
            if (isset($gerants[$index])) {
                User::create([
                    'nom' => $gerants[$index]['nom'],
                    'email' => $gerants[$index]['email'],
                    'password' => Hash::make($gerants[$index]['password']),
                    'role' => 'gerant',
                    'boutique_id' => $boutique->id,
                    'actif' => true,
                ]);
            }
        }

        echo "✅ " . count($gerants) . " gérants créés\n";
    }
}
