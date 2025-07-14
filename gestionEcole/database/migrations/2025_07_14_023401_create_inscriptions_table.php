<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('inscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves');
            $table->foreignId('classe_id')->constrained('classes');
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires');
            $table->date('date_inscription');
            $table->enum('statut', ['en_cours', 'termine', 'abandonne'])->default('en_cours');
            $table->text('observations')->nullable();
            $table->timestamps();

            $table->unique(['eleve_id', 'annee_scolaire_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('inscriptions');
    }
};
