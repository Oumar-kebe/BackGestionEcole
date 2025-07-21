<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('periodes', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->enum('type', ['trimestre', 'semestre']);
            $table->integer('ordre');
            $table->foreignId('annee_scolaire_id')->constrained('annees_scolaires');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->boolean('actuelle')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('periodes');
    }
};
