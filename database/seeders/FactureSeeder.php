<?php
namespace Database\Seeders;
use App\Models\Commande;
use App\Models\Facture;
use Illuminate\Database\Seeder;

class FactureSeeder extends Seeder
{
    public function run(): void
    {
        $commandes = Commande::where('statut', 'validee')->get();
        
        foreach ($commandes as $i => $cmd) {
            Facture::create([
                'commande_id' => $cmd->id,
                'numero_facture' => 'FAC-2026-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                'date_facture' => $cmd->date_validation ?? $cmd->date_commande,
                'montant_total' => $cmd->total,
                'statut' => 'active',
            ]);
        }
    }
}