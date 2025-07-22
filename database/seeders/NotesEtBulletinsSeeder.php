<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NotesEtBulletinsSeeder extends Seeder
{
    /**
     * Seeder spécialement pour générer uniquement les notes et bulletins
     * Utile si vous voulez ajouter ces données sans refaire tout le seeding
     */
    public function run()
    {
        $this->command->info('🎯 Génération des notes et bulletins uniquement...');
        
        $this->call([
            NoteSeeder::class,
            BulletinSeeder::class,
        ]);
        
        $this->command->info('✅ Notes et bulletins générés avec succès !');
    }
}
