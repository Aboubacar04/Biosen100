<?php
namespace Database\Seeders;
use App\Models\Commande;
use App\Models\Boutique;
use App\Models\Client;
use App\Models\Employe;
use App\Models\Livreur;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Faker\Factory as Faker;

class CommandeSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $boutique = Boutique::first();
        $clients = Client::all();
        $employes = Employe::all();
        $livreurs = Livreur::all();
        
        for ($i = 1; $i <= 150; $i++) {
            $date = Carbon::today()->subDays(rand(0, 30));
            $statut = $faker->randomElement(['en_cours', 'validee', 'validee', 'validee']);
            
            Commande::create([
                'numero_commande' => 'CMD-2026-' . str_pad($i, 5, '0', STR_PAD_LEFT),
                'boutique_id' => $boutique->id,
                'client_id' => $clients->random()->id,
                'employe_id' => $employes->random()->id,
                'livreur_id' => $livreurs->random()->id,
                'type_commande' => 'sur_place',
                'statut' => $statut,
                'total' => rand(10000, 150000),
                'date_commande' => $date,
                'date_validation' => $statut != 'en_cours' ? $date->copy()->addHours(1) : null,
            ]);
        }
    }
}