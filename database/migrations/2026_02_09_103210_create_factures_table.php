<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->unique()->constrained('commandes')->onDelete('cascade');
            $table->string('numero_facture')->unique();
            $table->datetime('date_facture');
            $table->decimal('montant_total', 10, 2);

            // 🔥 NOUVEAU : Statut de la facture
            $table->enum('statut', ['active', 'annulee'])->default('active');

            $table->timestamps();

            $table->index('numero_facture');
            $table->index('commande_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};
