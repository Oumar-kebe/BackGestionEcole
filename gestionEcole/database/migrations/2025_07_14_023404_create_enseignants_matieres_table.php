<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('enseignants_matieres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enseignant_id')->constrained('enseignants');
            $table->foreignId('matiere_id')->constrained('matieres');
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires');
            $table->timestamps();

            $table->unique(['enseignant_id', 'matiere_id', 'annee_scolaire_id'], 'unique_enseignant_matiere_annee');
        });
    }

    public function down()
    {
        Schema::dropIfExists('enseignants_matieres');
    }
};
