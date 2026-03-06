<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('distributeurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('telephone');
            $table->string('code_pin', 6)->nullable();
            $table->foreignId('boutique_id')->constrained()->onDelete('cascade');
            $table->boolean('actif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('distributeurs');
    }
};