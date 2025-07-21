<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves');
            $table->foreignId('matiere_id')->constrained('matieres');
            $table->foreignId('periode_id')->constrained('periodes');
            $table->foreignId('enseignant_id')->constrained('enseignants');
            $table->decimal('note_devoir1', 5, 2)->nullable();
            $table->decimal('note_devoir2', 5, 2)->nullable();
            $table->decimal('note_composition', 5, 2)->nullable();
            $table->decimal('moyenne', 5, 2)->nullable();
            $table->text('appreciation')->nullable();
            $table->timestamps();

            $table->unique(['eleve_id', 'matiere_id', 'periode_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('notes');
    }
};
