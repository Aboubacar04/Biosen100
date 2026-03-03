<?php

namespace Database\Seeders;

use App\Models\Boutique;
use Illuminate\Database\Seeder;

class BoutiqueSeeder extends Seeder
{
    public function run(): void
    {
        $boutiques = [
            ['nom' => 'BIOSEN100 Dakar', 'adresse' => 'Rue 14, Almadies, Dakar', 'telephone' => '+221 77 123 4567'],
            ['nom' => 'BIOSEN100 Plateau', 'adresse' => 'Boulevard de la République, Dakar', 'telephone' => '+221 77 234 5678'],
            ['nom' => 'BIOSEN100 Thiès', 'adresse' => 'Avenue Lamine Guèye, Thiès', 'telephone' => '+221 77 345 6789'],
            ['nom' => 'BIOSEN100 Kaolack', 'adresse' => 'Rue Ahmadou Cheikhou, Kaolack', 'telephone' => '+221 77 456 7890'],
            ['nom' => 'BIOSEN100 Tambacounda', 'adresse' => 'Boulevard Mali, Tambacounda', 'telephone' => '+221 77 567 8901'],
        ];

        foreach ($boutiques as $boutique) {
            Boutique::create($boutique);
        }

        $this->command->info('✅ 5 boutiques créées!');
    }
}