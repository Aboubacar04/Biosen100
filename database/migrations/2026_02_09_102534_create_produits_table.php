<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produits', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->text('description')->nullable();
            $table->decimal('prix_vente', 10, 2);
            $table->integer('stock')->default(0);
            $table->integer('seuil_alerte')->nullable();
            $table->string('image')->nullable();
            $table->foreignId('categorie_id')->constrained('categories')->onDelete('cascade');
            $table->foreignId('boutique_id')->constrained('boutiques')->onDelete('cascade');
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['boutique_id', 'categorie_id']);
            $table->index('stock');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produits');
    }
};
