<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('parents_eleves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('parents');
            $table->foreignId('eleve_id')->constrained('eleves');
            $table->enum('lien_parente', ['pere', 'mere', 'tuteur', 'autre']);
            $table->timestamps();

            $table->unique(['parent_id', 'eleve_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('parents_eleves');
    }
};
