<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Niveau;

class NiveauSeeder extends Seeder
{
    public function run()
    {
        $niveaux = [
            ['nom' => '6ème', 'code' => '6EME', 'ordre' => 1],
            ['nom' => '5ème', 'code' => '5EME', 'ordre' => 2],
            ['nom' => '4ème', 'code' => '4EME', 'ordre' => 3],
            ['nom' => '3ème', 'code' => '3EME', 'ordre' => 4],
            ['nom' => 'Seconde', 'code' => '2NDE', 'ordre' => 5],
            ['nom' => 'Première', 'code' => '1ERE', 'ordre' => 6],
            ['nom' => 'Terminale', 'code' => 'TERM', 'ordre' => 7],
        ];

        foreach ($niveaux as $niveau) {
            Niveau::create($niveau);
        }
    }
}
