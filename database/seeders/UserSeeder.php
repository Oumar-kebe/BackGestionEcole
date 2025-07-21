<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Créer l'administrateur principal
        User::create([
            'name' => 'Admin Principal',
            'email' => 'admin@gestionecole.com',
            'password' => Hash::make('admin123'),
            'nom' => 'Admin',
            'prenom' => 'Principal',
            'role' => 'administrateur',
            'telephone' => '77 123 45 67',
            'adresse' => 'Dakar, Sénégal',
            'sexe' => 'M',
            'matricule' => 'ADM' . date('Y') . '0001',
            'actif' => true,
            'email_verified_at' => now(),
        ]);

        // Créer un administrateur secondaire
        User::create([
            'name' => 'Fatou Diop',
            'email' => 'fatou.diop@gestionecole.com',
            'password' => Hash::make('admin123'),
            'nom' => 'Diop',
            'prenom' => 'Fatou',
            'role' => 'administrateur',
            'telephone' => '77 234 56 78',
            'adresse' => 'Dakar, Sénégal',
            'sexe' => 'F',
            'matricule' => 'ADM' . date('Y') . '0002',
            'actif' => true,
            'email_verified_at' => now(),
        ]);
    }
}
