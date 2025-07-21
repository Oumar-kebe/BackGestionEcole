<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Réinitialiser le cache des rôles et permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer le Super Administrateur s'il n'existe pas
        $superAdmin = User::firstOrCreate([
            'email' => 'superadmin@gestionecole.com'
        ], [
            'name' => 'Super Administrateur',
            'password' => Hash::make('superadmin123'),
            'nom' => 'Super',
            'prenom' => 'Admin',
            'role' => 'super-admin',
            'telephone' => '77 000 00 01',
            'adresse' => 'Dakar, Sénégal',
            'sexe' => 'M',
            'matricule' => 'SUP' . date('Y') . '0001',
            'actif' => true,
            'email_verified_at' => now(),
        ]);
        
        // Assigner le rôle si pas déjà assigné
        if (!$superAdmin->hasRole('super-admin')) {
            $superAdmin->assignRole('super-admin');
        }

        // Mettre à jour et assigner les rôles aux utilisateurs existants
        $users = [
            'admin@gestionecole.com' => 'administrateur',
            'fatou.diop@gestionecole.com' => 'administrateur',
        ];

        foreach ($users as $email => $roleName) {
            $user = User::where('email', $email)->first();
            if ($user) {
                $user->update(['role' => $roleName]);
                if (!$user->hasRole($roleName)) {
                    $user->assignRole($roleName);
                }
            }
        }

        // Créer de nouveaux utilisateurs uniquement s'ils n'existent pas
        $newUsers = [
            [
                'email' => 'directeur@gestionecole.com',
                'name' => 'Mamadou Seck',
                'nom' => 'Seck',
                'prenom' => 'Mamadou',
                'role' => 'directeur',
                'matricule' => 'DIR' . date('Y') . '0001'
            ],
            [
                'email' => 'prof.maths@gestionecole.com',
                'name' => 'Aminata Ba',
                'nom' => 'Ba',
                'prenom' => 'Aminata',
                'role' => 'enseignant',
                'matricule' => 'PROF' . date('Y') . '0001'
            ],
            [
                'email' => 'prof.francais@gestionecole.com',
                'name' => 'Omar Diallo',
                'nom' => 'Diallo',
                'prenom' => 'Omar',
                'role' => 'enseignant',
                'matricule' => 'PROF' . date('Y') . '0002'
            ],
            [
                'email' => 'prof.principal@gestionecole.com',
                'name' => 'Aïssatou Ndiaye',
                'nom' => 'Ndiaye',
                'prenom' => 'Aïssatou',
                'role' => 'professeur-principal',
                'matricule' => 'PP' . date('Y') . '0001'
            ],
            [
                'email' => 'parent@gestionecole.com',
                'name' => 'Ibrahima Fall',
                'nom' => 'Fall',
                'prenom' => 'Ibrahima',
                'role' => 'parent',
                'matricule' => 'PARENT' . date('Y') . '0001'
            ],
            [
                'email' => 'eleve@gestionecole.com',
                'name' => 'Khadija Sow',
                'nom' => 'Sow',
                'prenom' => 'Khadija',
                'role' => 'eleve',
                'matricule' => 'ELEV' . date('Y') . '0001'
            ],
            [
                'email' => 'secretaire@gestionecole.com',
                'name' => 'Marième Diop',
                'nom' => 'Diop',
                'prenom' => 'Marième',
                'role' => 'secretaire',
                'matricule' => 'SEC' . date('Y') . '0001'
            ],
            [
                'email' => 'comptable@gestionecole.com',
                'name' => 'Moussa Cissé',
                'nom' => 'Cissé',
                'prenom' => 'Moussa',
                'role' => 'comptable',
                'matricule' => 'CPT' . date('Y') . '0001'
            ]
        ];

        foreach ($newUsers as $userData) {
            $user = User::firstOrCreate([
                'email' => $userData['email']
            ], [
                'name' => $userData['name'],
                'password' => Hash::make('demo123'),
                'nom' => $userData['nom'],
                'prenom' => $userData['prenom'],
                'role' => $userData['role'],
                'telephone' => '77 ' . rand(100, 999) . ' ' . rand(10, 99) . ' ' . rand(10, 99),
                'adresse' => 'Dakar, Sénégal',
                'sexe' => rand(0, 1) ? 'M' : 'F',
                'matricule' => $userData['matricule'],
                'actif' => true,
                'email_verified_at' => now(),
            ]);
            
            // Assigner le rôle si pas déjà assigné
            if (!$user->hasRole($userData['role'])) {
                $user->assignRole($userData['role']);
            }
        }

        $this->command->info('Utilisateurs créés et rôles assignés avec succès!');
        $this->command->info('Comptes de test disponibles:');
        $this->command->info('- Super Admin: superadmin@gestionecole.com / superadmin123');
        $this->command->info('- Administrateur: admin@gestionecole.com / admin123');
        $this->command->info('- Directeur: directeur@gestionecole.com / demo123');
        $this->command->info('- Enseignant: prof.maths@gestionecole.com / demo123');
        $this->command->info('- Prof Principal: prof.principal@gestionecole.com / demo123');
        $this->command->info('- Parent: parent@gestionecole.com / demo123');
        $this->command->info('- Élève: eleve@gestionecole.com / demo123');
        $this->command->info('- Secrétaire: secretaire@gestionecole.com / demo123');
        $this->command->info('- Comptable: comptable@gestionecole.com / demo123');
    }
}
