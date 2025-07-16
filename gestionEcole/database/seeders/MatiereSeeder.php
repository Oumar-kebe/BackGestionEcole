<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Matiere;
use App\Models\Niveau;

class MatiereSeeder extends Seeder
{
    public function run()
    {
        $matieres = [
            // Matières communes à tous les niveaux
            ['nom' => 'Mathématiques', 'code' => 'MATH', 'coefficient' => 4],
            ['nom' => 'Français', 'code' => 'FR', 'coefficient' => 3],
            ['nom' => 'Anglais', 'code' => 'ANG', 'coefficient' => 2],
            ['nom' => 'Histoire-Géographie', 'code' => 'HG', 'coefficient' => 2],
            ['nom' => 'Sciences de la Vie et de la Terre', 'code' => 'SVT', 'coefficient' => 2],
            ['nom' => 'Physique-Chimie', 'code' => 'PC', 'coefficient' => 3],
            ['nom' => 'Éducation Physique et Sportive', 'code' => 'EPS', 'coefficient' => 1],
            ['nom' => 'Arts Plastiques', 'code' => 'ART', 'coefficient' => 1],
            ['nom' => 'Musique', 'code' => 'MUS', 'coefficient' => 1],
            ['nom' => 'Informatique', 'code' => 'INFO', 'coefficient' => 2],
        ];

        $niveaux = Niveau::all();

        foreach ($niveaux as $niveau) {
            foreach ($matieres as $matiere) {
                // Ajuster les coefficients selon le niveau
                $coefficient = $matiere['coefficient'];
                if ($niveau->ordre >= 5) { // Lycée
                    if (in_array($matiere['code'], ['MATH', 'PC'])) {
                        $coefficient += 1;
                    }
                }

                Matiere::create([
                    'nom' => $matiere['nom'],
                    'code' => $matiere['code'] . '_' . $niveau->code,
                    'coefficient' => $coefficient,
                    'niveau_id' => $niveau->id,
                    'actif' => true,
                ]);
            }
        }
    }
}
