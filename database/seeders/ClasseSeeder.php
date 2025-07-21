<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Classe;
use App\Models\Niveau;
use App\Models\AnneeScolaire;

class ClasseSeeder extends Seeder
{
    public function run()
    {
        $anneeCourante = AnneeScolaire::where('actuelle', true)->first();
        $niveaux = Niveau::all();

        foreach ($niveaux as $niveau) {
            // Créer 2 ou 3 classes par niveau
            $nbClasses = $niveau->ordre <= 4 ? 3 : 2; // Plus de classes au collège

            for ($i = 1; $i <= $nbClasses; $i++) {
                Classe::create([
                    'nom' => $niveau->nom . ' ' . chr(64 + $i), // 6ème A, 6ème B, etc.
                    'niveau_id' => $niveau->id,
                    'annee_scolaire_id' => $anneeCourante->id,
                    'capacite' => 30,
                    'actif' => true,
                ]);
            }
        }
    }
}
