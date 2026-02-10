<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Vérifier si l'admin existe déjà
        $existingAdmin = User::where('email', 'aboubacrisow99@gmail.com')->first();

        if ($existingAdmin) {
            echo "⚠️ Admin existe déjà!\n";
            return;
        }

        // Créer l'admin par défaut
        User::create([
            'nom' => 'Aboubacar Sow',
            'email' => 'aboubacrisow99@gmail.com',
            'password' => Hash::make('SENEGAL390a'),
            'role' => 'admin',
            'boutique_id' => null,
            'actif' => true,
        ]);

        echo "✅ Admin créé avec succès!\n";
        echo "📧 Email: aboubacrisow99@gmail.com\n";
        echo "🔑 Password: SENEGAL390a\n";
    }
}
