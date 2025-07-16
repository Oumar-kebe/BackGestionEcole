<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Enseignant;
use App\Models\Matiere;
use App\Models\AnneeScolaire;
use Illuminate\Support\Facades\Hash;

class EnseignantSeeder extends Seeder
{
    public function run()
    {
        $anneeCourante = AnneeScolaire::where('actuelle', true)->first();

        $enseignants = [
            [
                'user' => [
                    'nom' => 'Ndiaye',
                    'prenom' => 'Moussa',
                    'email' => 'moussa.ndiaye@gestionecole.com',
                    'sexe' => 'M',
                    'telephone' => '77 345 67 89',
                    'adresse' => 'Pikine, Dakar',
                ],
                'enseignant' => [
                    'specialite' => 'Mathématiques',
                    'diplome' => 'Master en Mathématiques',
                    'annees_experience' => 10,
                ],
                'matieres' => ['MATH']
            ],
            [
                'user' => [
                    'nom' => 'Fall',
                    'prenom' => 'Aminata',
                    'email' => 'aminata.fall@gestionecole.com',
                    'sexe' => 'F',
                    'telephone' => '77 456 78 90',
                    'adresse' => 'Grand Dakar',
                ],
                'enseignant' => [
                    'specialite' => 'Lettres Modernes',
                    'diplome' => 'Master en Lettres',
                    'annees_experience' => 8,
                ],
                'matieres' => ['FR']
            ],
            [
                'user' => [
                    'nom' => 'Sow',
                    'prenom' => 'Ibrahima',
                    'email' => 'ibrahima.sow@gestionecole.com',
                    'sexe' => 'M',
                    'telephone' => '77 567 89 01',
                    'adresse' => 'Médina, Dakar',
                ],
                'enseignant' => [
                    'specialite' => 'Sciences Physiques',
                    'diplome' => 'Ingénieur + CAPES',
                    'annees_experience' => 12,
                ],
                'matieres' => ['PC', 'SVT']
            ],
            [
                'user' => [
                    'nom' => 'Diallo',
                    'prenom' => 'Mariama',
                    'email' => 'mariama.diallo@gestionecole.com',
                    'sexe' => 'F',
                    'telephone' => '77 678 90 12',
                    'adresse' => 'Parcelles Assainies',
                ],
                'enseignant' => [
                    'specialite' => 'Anglais',
                    'diplome' => 'Master en Anglais',
                    'annees_experience' => 6,
                ],
                'matieres' => ['ANG']
            ],
            [
                'user' => [
                    'nom' => 'Ba',
                    'prenom' => 'Ousmane',
                    'email' => 'ousmane.ba@gestionecole.com',
                    'sexe' => 'M',
                    'telephone' => '77 789 01 23',
                    'adresse' => 'Rufisque',
                ],
                'enseignant' => [
                    'specialite' => 'Histoire-Géographie',
                    'diplome' => 'Master en Histoire',
                    'annees_experience' => 15,
                ],
                'matieres' => ['HG']
            ],
        ];

        foreach ($enseignants as $data) {
            // Créer l'utilisateur
            $user = User::create(array_merge($data['user'], [
                'name' => $data['user']['prenom'] . ' ' . $data['user']['nom'],
                'password' => Hash::make('password123'),
                'role' => 'enseignant',
                'matricule' => 'ENS' . date('Y') . str_pad(User::where('role', 'enseignant')->count() + 1, 4, '0', STR_PAD_LEFT),
                'actif' => true,
                'email_verified_at' => now(),
                'date_naissance' => fake()->dateTimeBetween('-60 years', '-25 years'),
                'lieu_naissance' => 'Dakar',
            ]));

            // Créer le profil enseignant
            $enseignant = Enseignant::create(array_merge($data['enseignant'], [
                'user_id' => $user->id,
            ]));

            // Assigner les matières
            foreach ($data['matieres'] as $codeMatiere) {
                $matieres = Matiere::where('code', 'like', $codeMatiere . '%')->get();
                foreach ($matieres as $matiere) {
                    $enseignant->matieres()->attach($matiere->id, [
                        'annee_scolaire_id' => $anneeCourante->id
                    ]);

                    // Assigner l'enseignant aux classes du niveau de la matière
                    $classes = $matiere->niveau->classes()
                        ->where('annee_scolaire_id', $anneeCourante->id)
                        ->get();

                    foreach ($classes as $classe) {
                        $enseignant->classes()->attach($classe->id, [
                            'matiere_id' => $matiere->id
                        ]);
                    }
                }
            }
        }
    }
}
