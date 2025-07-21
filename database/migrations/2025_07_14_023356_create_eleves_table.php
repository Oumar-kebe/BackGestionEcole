<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('eleves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('nationalite')->nullable();
            $table->string('groupe_sanguin')->nullable();
            $table->text('allergies')->nullable();
            $table->text('maladies')->nullable();
            $table->string('personne_urgence_nom')->nullable();
            $table->string('personne_urgence_telephone')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('eleves');
    }
};
