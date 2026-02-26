<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
   public function up(): void
{
    Schema::create('commande_bisens', function (Blueprint $table) {
        $table->id();
        $table->string('telephone');
        $table->string('nom_client');
        $table->string('adresse')->nullable();
        $table->string('commercial')->nullable();
        $table->text('produits');
        $table->foreignId('saisie_par')->constrained('users')->onDelete('cascade');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('commande_bisens');
}
    
};
