<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gammes', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->foreignId('boutique_id')->constrained()->onDelete('cascade');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });

        Schema::create('gamme_produit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gamme_id')->constrained()->onDelete('cascade');
            $table->foreignId('produit_id')->constrained()->onDelete('cascade');
            $table->integer('quantite')->default(1);
            $table->timestamps();

            $table->unique(['gamme_id', 'produit_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gamme_produit');
        Schema::dropIfExists('gammes');
    }
};