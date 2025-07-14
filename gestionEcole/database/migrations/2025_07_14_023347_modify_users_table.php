<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('nom');
            $table->string('prenom');
            $table->enum('role', ['administrateur', 'enseignant', 'eleve', 'parent']);
            $table->string('telephone')->nullable();
            $table->string('adresse')->nullable();
            $table->date('date_naissance')->nullable();
            $table->string('lieu_naissance')->nullable();
            $table->enum('sexe', ['M', 'F'])->nullable();
            $table->string('matricule')->unique()->nullable();
            $table->boolean('actif')->default(true);
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nom', 'prenom', 'role', 'telephone', 'adresse',
                'date_naissance', 'lieu_naissance', 'sexe', 'matricule',
                'actif']);
            $table->dropSoftDeletes();
        });
    }
};
