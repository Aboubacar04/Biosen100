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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['admin', 'gerant'])->default('gerant');
            $table->foreignId('boutique_id')->nullable()->constrained('boutiques')->onDelete('cascade');
            $table->string('photo')->nullable();
            $table->boolean('actif')->default(true);
            $table->rememberToken();
            $table->timestamps();

            // Index pour performance
            $table->index(['boutique_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
