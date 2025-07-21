<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AnneeScolaire;

class AnneeScolaireSeeder extends Seeder
{
    public function run()
    {
        // Année scolaire précédente
        AnneeScolaire::create([
            'libelle' => '2023-2024',
            'date_debut' => '2023-10-02',
            'date_fin' => '2024-07-31',
            'actuelle' => false,
        ]);

        // Année scolaire actuelle
        AnneeScolaire::create([
            'libelle' => '2024-2025',
            'date_debut' => '2024-10-01',
            'date_fin' => '2025-07-31',
            'actuelle' => true,
        ]);
    }
}
