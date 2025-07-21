<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

class SimpleTestUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Réinitialiser le cache des rôles et permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer des comptes de test simples avec des emails compatibles frontend
        $testUsers = [
            [
                'email' => 'admin@ecole.com',
                'password' => 'password',
                'name' => 'Admin Ecole',
                'nom' => 'Admin',
                'prenom' => 'Ecole',
                'role' => 'administrateur',
                'spatie_role' => 'administrateur'
            ],
            [
                'email' => 'prof@ecole.com',
                'password' => 'password',
                'name' => 'Prof Ecole',
                'nom' => 'Prof',
                'prenom' => 'Ecole',
                'role' => 'enseignant',
                'spatie_role' => 'enseignant'
            ],
            [
                'email' => 'parent@ecole.com',
                'password' => 'password',
                'name' => 'Parent Ecole',
                'nom' => 'Parent',
                'prenom' => 'Ecole',
                'role' => 'parent',
                'spatie_role' => 'parent'
            ],
            [
                'email' => 'eleve@ecole.com',
                'password' => 'password',
                'name' => 'Eleve Ecole',
                'nom' => 'Eleve',
                'prenom' => 'Ecole',
                'role' => 'eleve',
                'spatie_role' => 'eleve'
            ]
        ];

        foreach ($testUsers as $index => $userData) {
            $user = User::firstOrCreate([
                'email' => $userData['email']
            ], [
                'name' => $userData['name'],
                'password' => Hash::make($userData['password']),
                'nom' => $userData['nom'],
                'prenom' => $userData['prenom'],
                'role' => $userData['role'],
                'telephone' => '77 ' . str_pad($index + 1, 3, '0', STR_PAD_LEFT) . ' 00 00',
                'adresse' => 'Dakar, Sénégal',
                'sexe' => 'M',
                'matricule' => strtoupper(substr($userData['role'], 0, 3)) . date('Y') . str_pad($index + 10, 4, '0', STR_PAD_LEFT),
                'actif' => true,
                'email_verified_at' => now(),
            ]);
            
            // Assigner le rôle Spatie si pas déjà assigné
            if (!$user->hasRole($userData['spatie_role'])) {
                $user->assignRole($userData['spatie_role']);
            }

            $this->command->info("Utilisateur créé: {$userData['email']} avec rôle {$userData['spatie_role']}");
        }

        $this->command->info('Comptes de test compatibles frontend créés!');
        $this->command->info('Comptes disponibles:');
        $this->command->info('- Admin: admin@ecole.com / password');
        $this->command->info('- Enseignant: prof@ecole.com / password');
        $this->command->info('- Parent: parent@ecole.com / password');
        $this->command->info('- Élève: eleve@ecole.com / password');
    }
}
