<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Réinitialiser le cache des rôles
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Créer le rôle Super Administrateur
        $superAdmin = Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'api'
        ]);

        // Créer le rôle Administrateur
        $admin = Role::firstOrCreate([
            'name' => 'administrateur',
            'guard_name' => 'api'
        ]);

        // Créer le rôle Directeur
        $director = Role::firstOrCreate([
            'name' => 'directeur',
            'guard_name' => 'api'
        ]);

        // Créer le rôle Enseignant
        $teacher = Role::firstOrCreate([
            'name' => 'enseignant',
            'guard_name' => 'api'
        ]);

        // Créer le rôle Professeur Principal
        $mainTeacher = Role::firstOrCreate([
            'name' => 'professeur-principal',
            'guard_name' => 'api'
        ]);

        // Créer le rôle Surveillant
        $supervisor = Role::firstOrCreate([
            'name' => 'surveillant',
            'guard_name' => 'api'
        ]);

        // Créer le rôle Parent
        $parent = Role::firstOrCreate([
            'name' => 'parent',
            'guard_name' => 'api'
        ]);

        // Créer le rôle Élève
        $student = Role::firstOrCreate([
            'name' => 'eleve',
            'guard_name' => 'api'
        ]);

        // Créer le rôle Secrétaire
        $secretary = Role::firstOrCreate([
            'name' => 'secretaire',
            'guard_name' => 'api'
        ]);

        // Créer le rôle Comptable
        $accountant = Role::firstOrCreate([
            'name' => 'comptable',
            'guard_name' => 'api'
        ]);

        $this->command->info('Rôles créés avec succès!');
        $this->command->info('Rôles créés: super-admin, administrateur, directeur, enseignant, professeur-principal, surveillant, parent, eleve, secretaire, comptable');
    }
}
