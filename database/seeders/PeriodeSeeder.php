<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Periode;
use App\Models\AnneeScolaire;

class PeriodeSeeder extends Seeder
{
    public function run()
    {
        $anneeCourante = AnneeScolaire::where('actuelle', true)->first();

        // Créer les trimestres pour l'année courante
        $periodes = [
            [
                'nom' => '1er Trimestre',
                'type' => 'trimestre',
                'ordre' => 1,
                'date_debut' => '2024-10-01',
                'date_fin' => '2024-12-20',
                'actuelle' => false,
            ],
            [
                'nom' => '2ème Trimestre',
                'type' => 'trimestre',
                'ordre' => 2,
                'date_debut' => '2025-01-06',
                'date_fin' => '2025-03-28',
                'actuelle' => true, // Trimestre actuel
            ],
            [
                'nom' => '3ème Trimestre',
                'type' => 'trimestre',
                'ordre' => 3,
                'date_debut' => '2025-04-07',
                'date_fin' => '2025-07-31',
                'actuelle' => false,
            ],
        ];

        foreach ($periodes as $periode) {
            Periode::create(array_merge($periode, [
                'annee_scolaire_id' => $anneeCourante->id,
            ]));
        }
    }
}
