<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('livreurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('telephone')->nullable();
            $table->boolean('disponible')->default(true);
            $table->foreignId('boutique_id')->constrained('boutiques')->onDelete('cascade');
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['boutique_id', 'disponible']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('livreurs');
    }
};
