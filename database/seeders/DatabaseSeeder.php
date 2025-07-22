<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            // 1. Création des permissions et rôles
            PermissionSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
            
            // 2. Création des utilisateurs de base
            UserSeeder::class,
            
            // 3. Assignation des rôles aux utilisateurs
            UserRoleSeeder::class,
            
            // 4. Données scolaires
            AnneeScolaireSeeder::class,
            NiveauSeeder::class,
            MatiereSeeder::class,
            ClasseSeeder::class,
            EnseignantSeeder::class,
            EleveSeeder::class,
            PeriodeSeeder::class,
            EmploiTempsSeeder::class,
            
            // 5. Génération des notes (doit être avant les bulletins)
            NoteSeeder::class,
            
            // 6. Génération des bulletins (doit être après les notes)
            BulletinSeeder::class,
        ]);
    }
}
