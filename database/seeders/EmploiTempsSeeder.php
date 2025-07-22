<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EmploiTemps;

class EmploiTempsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $emplois = [
            // Lundi
            [
                'jour' => 'Lundi',
                'heure_debut' => '08:00',
                'heure_fin' => '10:00',
                'matiere' => 'Mathématiques',
                'professeur' => 'Prof. Diallo',
                'salle' => 'A101',
                'classe' => 'Terminale S1',
                'niveau' => 'Terminale',
                'description' => 'Cours de mathématiques - Analyse'
            ],
            [
                'jour' => 'Lundi',
                'heure_debut' => '10:15',
                'heure_fin' => '12:15',
                'matiere' => 'Physique-Chimie',
                'professeur' => 'Prof. Sow',
                'salle' => 'B102',
                'classe' => 'Terminale S1',
                'niveau' => 'Terminale',
                'description' => 'TP de physique - Optique'
            ],
            [
                'jour' => 'Lundi',
                'heure_debut' => '14:00',
                'heure_fin' => '16:00',
                'matiere' => 'Français',
                'professeur' => 'Prof. Kane',
                'salle' => 'C201',
                'classe' => 'Terminale S1',
                'niveau' => 'Terminale',
                'description' => 'Étude de texte - Littérature française'
            ],

            // Mardi
            [
                'jour' => 'Mardi',
                'heure_debut' => '08:00',
                'heure_fin' => '10:00',
                'matiere' => 'SVT',
                'professeur' => 'Prof. Ba',
                'salle' => 'D103',
                'classe' => 'Terminale S1',
                'niveau' => 'Terminale',
                'description' => 'Biologie - Génétique'
            ],
            [
                'jour' => 'Mardi',
                'heure_debut' => '10:15',
                'heure_fin' => '12:15',
                'matiere' => 'Histoire-Géographie',
                'professeur' => 'Prof. Ndiaye',
                'salle' => 'A201',
                'classe' => 'Terminale S1',
                'niveau' => 'Terminale',
                'description' => 'Histoire contemporaine'
            ],
            [
                'jour' => 'Mardi',
                'heure_debut' => '14:00',
                'heure_fin' => '16:00',
                'matiere' => 'Anglais',
                'professeur' => 'Prof. Johnson',
                'salle' => 'B201',
                'classe' => 'Terminale S1',
                'niveau' => 'Terminale',
                'description' => 'Expression orale et écrite'
            ],

            // Mercredi
            [
                'jour' => 'Mercredi',
                'heure_debut' => '08:00',
                'heure_fin' => '10:00',
                'matiere' => 'Mathématiques',
                'professeur' => 'Prof. Diallo',
                'salle' => 'A101',
                'classe' => 'Terminale S1',
                'niveau' => 'Terminale',
                'description' => 'Exercices dirigés - Géométrie'
            ],
            [
                'jour' => 'Mercredi',
                'heure_debut' => '10:15',
                'heure_fin' => '12:15',
                'matiere' => 'Philosophie',
                'professeur' => 'Prof. Sarr',
                'salle' => 'C101',
                'classe' => 'Terminale S1',
                'niveau' => 'Terminale',
                'description' => 'Métaphysique et logique'
            ],

            // Jeudi
            [
                'jour' => 'Jeudi',
                'heure_debut' => '08:00',
                'heure_fin' => '10:00',
                'matiere' => 'Physique-Chimie',
                'professeur' => 'Prof. Sow',
                'salle' => 'B102',
                'classe' => 'Terminale S1',
                'niveau' => 'Terminale',
                'description' => 'Cours théorique - Mécanique'
            ],
            [
                'jour' => 'Jeudi',
                'heure_debut' => '10:15',
                'heure_fin' => '12:15',
                'matiere' => 'SVT',
                'professeur' => 'Prof. Ba',
                'salle' => 'D103',
                'classe' => 'Terminale S1',
                'niveau' => 'Terminale',
                'description' => 'TP Biologie - Microscopie'
            ],
            [
                'jour' => 'Jeudi',
                'heure_debut' => '14:00',
                'heure_fin' => '16:00',
                'matiere' => 'EPS',
                'professeur' => 'Prof. Ndao',
                'salle' => 'Gymnase',
                'classe' => 'Terminale S1',
                'niveau' => 'Terminale',
                'description' => 'Sport collectif - Basketball'
            ],

            // Vendredi
            [
                'jour' => 'Vendredi',
                'heure_debut' => '08:00',
                'heure_fin' => '10:00',
                'matiere' => 'Français',
                'professeur' => 'Prof. Kane',
                'salle' => 'C201',
                'classe' => 'Terminale S1',
                'niveau' => 'Terminale',
                'description' => 'Dissertation - Méthodologie'
            ],
            [
                'jour' => 'Vendredi',
                'heure_debut' => '10:15',
                'heure_fin' => '12:15',
                'matiere' => 'Mathématiques',
                'professeur' => 'Prof. Diallo',
                'salle' => 'A101',
                'classe' => 'Terminale S1',
                'niveau' => 'Terminale',
                'description' => 'Évaluation - Contrôle continu'
            ]
        ];

        foreach ($emplois as $emploi) {
            EmploiTemps::create($emploi);
        }
    }
}
