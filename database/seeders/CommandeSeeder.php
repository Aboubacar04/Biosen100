<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Commande;
use App\Models\Produit;
use App\Models\Boutique;
use App\Models\Client;
use App\Models\Employe;
use App\Models\Livreur;
use Faker\Factory as Faker;
use Carbon\Carbon;

class CommandeSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('fr_FR');

        $boutiques = Boutique::where('actif', true)->get();
        $count = 0;

        foreach ($boutiques as $boutique) {
            $clients = Client::where('boutique_id', $boutique->id)->get();
            $employes = Employe::where('boutique_id', $boutique->id)->get();
            $livreurs = Livreur::where('boutique_id', $boutique->id)->get();
            $produits = Produit::where('boutique_id', $boutique->id)->get();

            if ($clients->isEmpty() || $employes->isEmpty() || $produits->isEmpty()) {
                continue;
            }

            // Créer des commandes sur les 30 derniers jours
            for ($jour = 30; $jour >= 0; $jour--) {
                $date = Carbon::now()->subDays($jour);

                // 3-10 commandes par jour
                $nombreCommandes = $faker->numberBetween(3, 10);

                for ($i = 0; $i < $nombreCommandes; $i++) {
                    $typeCommande = $faker->randomElement(['sur_place', 'livraison']);
                    $livreurId = ($typeCommande === 'livraison' && $livreurs->isNotEmpty())
                        ? $livreurs->random()->id
                        : null;

                    // Déterminer le statut (plus on est proche d'aujourd'hui, plus de en_cours)
                    if ($jour <= 2) {
                        $statutFinal = $faker->randomElement(['en_cours', 'validee']);
                    } else {
                        $statutFinal = $faker->randomElement(['validee', 'validee', 'validee', 'annulee']);
                    }

                    // ✅ CRÉER TOUJOURS avec statut "en_cours" d'abord
                    $commande = Commande::create([
                        'boutique_id' => $boutique->id,
                        'client_id' => $clients->random()->id,
                        'employe_id' => $employes->random()->id,
                        'livreur_id' => $livreurId,
                        'type_commande' => $typeCommande,
                        'statut' => 'en_cours', // ✅ Toujours en_cours au début
                        'total' => 0,
                        'date_commande' => $date,
                        'notes' => $faker->optional(0.3)->sentence(),
                        'created_at' => $date,
                        'updated_at' => $date,
                    ]);

                    // Ajouter 2-5 produits à la commande
                    $nombreProduits = $faker->numberBetween(2, 5);
                    $produitsCommande = $produits->random($nombreProduits);
                    $total = 0;

                    foreach ($produitsCommande as $produit) {
                        $quantite = $faker->numberBetween(1, 5);
                        $prixUnitaire = $produit->prix_vente;
                        $sousTotal = $quantite * $prixUnitaire;

                        $commande->produits()->attach($produit->id, [
                            'quantite' => $quantite,
                            'prix_unitaire' => $prixUnitaire,
                            'sous_total' => $sousTotal,
                        ]);

                        $total += $sousTotal;
                    }

                    // Mettre à jour le total
                    $commande->update(['total' => $total]);

                    // ✅ MAINTENANT on change le statut selon le cas
                    if ($statutFinal === 'validee') {
                        // Valider la commande (change statut, décrémente stock, crée facture)
                        $commande->valider();
                        $commande->date_validation = $date;
                        $commande->save();
                    } elseif ($statutFinal === 'annulee') {
                        // D'abord valider pour avoir du stock à remettre
                        $commande->valider();

                        // Puis annuler
                        $raisons = [
                            'Client a changé d\'avis',
                            'Produit non disponible',
                            'Erreur de saisie',
                            'Demande du client',
                        ];
                        $commande->annuler($raisons[array_rand($raisons)], 1); // Admin annule

                        // Ajuster les dates
                        $commande->date_validation = $date;
                        $commande->date_annulation = $date->copy()->addHours($faker->numberBetween(1, 5));
                        $commande->save();
                    }
                    // Si en_cours, on ne fait rien de plus

                    $count++;
                }
            }
        }

        echo "✅ $count commandes créées\n";
    }
}
