<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Champs livraison sur commandes
        Schema::table('commandes', function (Blueprint $table) {
            $table->enum('statut_livraison', ['en_attente', 'assignee', 'livree'])
                  ->nullable()->after('statut');
            $table->timestamp('date_livraison')->nullable()->after('date_annulation');
        });

        // Code PIN pour livreurs
        Schema::table('livreurs', function (Blueprint $table) {
            $table->string('code_pin', 6)->nullable()->after('telephone');
        });
    }

    public function down(): void
    {
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropColumn(['statut_livraison', 'date_livraison']);
        });

        Schema::table('livreurs', function (Blueprint $table) {
            $table->dropColumn('code_pin');
        });
    }
};