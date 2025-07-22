#!/bin/bash

echo "🎯 Test des seeders Notes et Bulletins"
echo "======================================="

cd /Users/kahtech/Documents/isi/laravel/examenDaneLo/backebd-ecole

echo ""
echo "📊 Comptage des données avant seeding:"
php artisan tinker --execute="
echo 'Notes existantes: ' . \App\Models\Note::count();
echo 'Bulletins existants: ' . \App\Models\Bulletin::count();
echo 'Élèves inscrits: ' . \App\Models\Inscription::where('statut', 'en_cours')->count();
echo 'Classes actives: ' . \App\Models\Classe::count();
echo 'Périodes: ' . \App\Models\Periode::count();
"

echo ""
echo "🚀 Exécution du seeder NotesEtBulletinsSeeder..."
php artisan db:seed --class=NotesEtBulletinsSeeder

echo ""
echo "📊 Comptage des données après seeding:"
php artisan tinker --execute="
echo 'Notes générées: ' . \App\Models\Note::count();
echo 'Bulletins générés: ' . \App\Models\Bulletin::count();
echo 'Moyenne générale des bulletins: ' . round(\App\Models\Bulletin::avg('moyenne_generale'), 2) . '/20';
"

echo ""
echo "✅ Test terminé !"
