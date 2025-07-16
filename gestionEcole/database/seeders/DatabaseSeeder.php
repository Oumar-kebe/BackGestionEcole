<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call([
            UserSeeder::class,
            AnneeScolaireSeeder::class,
            NiveauSeeder::class,
            MatiereSeeder::class,
            ClasseSeeder::class,
            EnseignantSeeder::class,
            EleveSeeder::class,
            PeriodeSeeder::class,
        ]);
    }
}
