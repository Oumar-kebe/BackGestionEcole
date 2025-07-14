<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('enseignants_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enseignant_id')->constrained('enseignants');
            $table->foreignId('classe_id')->constrained('classes');
            $table->foreignId('matiere_id')->constrained('matieres');
            $table->timestamps();

            $table->unique(['enseignant_id', 'classe_id', 'matiere_id'], 'unique_enseignant_classe_matiere');
        });
    }

    public function down()
    {
        Schema::dropIfExists('enseignants_classes');
    }
};
