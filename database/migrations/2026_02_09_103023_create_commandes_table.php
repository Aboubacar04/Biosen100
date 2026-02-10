<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->string('numero_commande')->unique();
            $table->foreignId('boutique_id')->constrained('boutiques')->onDelete('cascade');
            $table->foreignId('client_id')->nullable()->constrained('clients')->onDelete('set null');
            $table->foreignId('employe_id')->constrained('employes')->onDelete('cascade');
            $table->foreignId('livreur_id')->nullable()->constrained('livreurs')->onDelete('set null');
            $table->enum('type_commande', ['sur_place', 'livraison']);
            $table->enum('statut', ['en_cours', 'validee', 'annulee'])->default('en_cours');
            $table->decimal('total', 10, 2)->default(0);
            $table->date('date_commande');
            $table->datetime('date_validation')->nullable();

            // 🔥 NOUVEAUX CHAMPS POUR L'ANNULATION
            $table->datetime('date_annulation')->nullable();
            $table->text('raison_annulation')->nullable();
            $table->foreignId('annulee_par')->nullable()->constrained('users')->onDelete('set null');

            $table->text('notes')->nullable();
            $table->timestamps();

            // Index
            $table->index(['boutique_id', 'statut']);
            $table->index(['date_commande', 'statut']);
            $table->index('numero_commande');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commandes');
    }
};
