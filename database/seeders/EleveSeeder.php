<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Eleve;
use App\Models\ParentEleve;
use App\Models\Inscription;
use App\Models\Classe;
use App\Models\AnneeScolaire;
use Illuminate\Support\Facades\Hash;

class EleveSeeder extends Seeder
{
    public function run()
    {
        $anneeCourante = AnneeScolaire::where('actuelle', true)->first();
        $classes = Classe::where('annee_scolaire_id', $anneeCourante->id)->get();

        $prenomsMasculins = ['Moussa', 'Ibrahima', 'Amadou', 'Cheikh','Mouhamed' ,'Omar', 'Aliou', 'Babacar'];
        $prenomsFeminins = ['Fatou', 'Aminata', 'Mariama', 'Aissatou', 'Khady', 'Ndèye', 'Mame', 'Aisha'];
        $noms = ['Ndiaye', 'Fall', 'Diop', 'Sow', 'Ba', 'Diallo', 'Sarr', 'Gueye', 'Seck', 'Faye'];

        foreach ($classes as $classe) {
            // Créer 20 à 25 élèves par classe
            $nbEleves = rand(20, 25);

            for ($i = 0; $i < $nbEleves; $i++) {
                $sexe = rand(0, 1) ? 'M' : 'F';
                $prenom = $sexe === 'M'
                    ? $prenomsMasculins[array_rand($prenomsMasculins)]
                    : $prenomsFeminins[array_rand($prenomsFeminins)];
                $nom = $noms[array_rand($noms)];

                // Créer l'utilisateur élève
                $userEleve = User::create([
                    'name' => $prenom . ' ' . $nom,
                    'nom' => $nom,
                    'prenom' => $prenom,
                    'email' => strtolower($prenom . '.' . $nom . rand(100, 999)) . '@eleve.gestionecole.com',
                    'password' => Hash::make('password123'),
                    'role' => 'eleve',
                    'sexe' => $sexe,
                    'telephone' => '77 ' . rand(100, 999) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                    'adresse' => 'Dakar, Sénégal',
                    'date_naissance' => fake()->dateTimeBetween('-18 years', '-10 years'),
                    'lieu_naissance' => 'Dakar',
                    'matricule' => 'ELV' . date('Y') . str_pad(User::where('role', 'eleve')->count() + 1, 4, '0', STR_PAD_LEFT),
                    'actif' => true,
                ]);

                // Créer le profil élève
                $eleve = Eleve::create([
                    'user_id' => $userEleve->id,
                    'nationalite' => 'Sénégalaise',
                    'groupe_sanguin' => ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'][rand(0, 7)],
                    'personne_urgence_nom' => 'Contact Urgence ' . $nom,
                    'personne_urgence_telephone' => '77 ' . rand(100, 999) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                ]);

                // Créer ou récupérer le parent
                $emailParent = strtolower('parent.' . $nom . rand(1, 100)) . '@parent.gestionecole.com';
                $userParent = User::where('email', $emailParent)->first();

                if (!$userParent) {
                    $prenomParent = $sexe === 'M'
                        ? $prenomsFeminins[array_rand($prenomsFeminins)] // Mère
                        : $prenomsMasculins[array_rand($prenomsMasculins)]; // Père

                    $userParent = User::create([
                        'name' => $prenomParent . ' ' . $nom,
                        'nom' => $nom,
                        'prenom' => $prenomParent,
                        'email' => $emailParent,
                        'password' => Hash::make('password123'),
                        'role' => 'parent',
                        'sexe' => $sexe === 'M' ? 'F' : 'M',
                        'telephone' => '77 ' . rand(100, 999) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                        'adresse' => 'Dakar, Sénégal',
                        'matricule' => 'PAR' . date('Y') . str_pad(User::where('role', 'parent')->count() + 1, 4, '0', STR_PAD_LEFT),
                        'actif' => true,
                    ]);

                    ParentEleve::create([
                        'user_id' => $userParent->id,
                        'profession' => ['Enseignant', 'Médecin', 'Ingénieur', 'Commerçant', 'Fonctionnaire'][rand(0, 4)],
                        'lieu_travail' => 'Dakar',
                    ]);
                }

                $parent = ParentEleve::where('user_id', $userParent->id)->first();

                // Lier le parent à l'élève
                $eleve->parents()->attach($parent->id, [
                    'lien_parente' => $sexe === 'M' ? 'mere' : 'pere'
                ]);

                // Inscrire l'élève dans la classe
                Inscription::create([
                    'eleve_id' => $eleve->id,
                    'classe_id' => $classe->id,
                    'annee_scolaire_id' => $anneeCourante->id,
                    'date_inscription' => fake()->dateTimeBetween($anneeCourante->date_debut, 'now'),
                    'statut' => 'en_cours',
                ]);
            }
        }
    }
}
