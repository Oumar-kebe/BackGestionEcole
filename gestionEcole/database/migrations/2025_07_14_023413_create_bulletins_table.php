<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bulletins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('eleve_id')->constrained('eleves');
            $table->foreignId('classe_id')->constrained('classes');
            $table->foreignId('periode_id')->constrained('periodes');
            $table->decimal('moyenne_generale', 5, 2)->nullable();
            $table->integer('rang')->nullable();
            $table->integer('effectif_classe')->nullable();
            $table->enum('mention', ['excellent', 'tres_bien', 'bien', 'assez_bien', 'passable', 'insuffisant'])->nullable();
            $table->text('observation_conseil')->nullable();
            $table->string('fichier_pdf')->nullable();
            $table->timestamp('genere_le')->nullable();
            $table->timestamps();

            $table->unique(['eleve_id', 'periode_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('bulletins');
    }
};
