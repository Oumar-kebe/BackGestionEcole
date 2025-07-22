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
        Schema::create('emploi_temps', function (Blueprint $table) {
            $table->id();
            $table->enum('jour', ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi']);
            $table->time('heure_debut');
            $table->time('heure_fin');
            $table->string('matiere');
            $table->string('professeur');
            $table->string('salle');
            $table->string('classe');
            $table->string('niveau')->nullable();
            $table->text('description')->nullable();
            $table->enum('statut', ['actif', 'annule', 'reporte'])->default('actif');
            $table->timestamps();

            // Index pour optimiser les requêtes
            $table->index(['jour', 'heure_debut']);
            $table->index(['classe', 'jour']);
            $table->index(['professeur', 'jour']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('emploi_temps');
    }
};
