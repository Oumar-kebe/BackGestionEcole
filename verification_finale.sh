#!/bin/bash

echo "🎯 VÉRIFICATION FINALE DES SEEDERS"
echo "=================================="

cd /Users/kahtech/Documents/isi/laravel/examenDaneLo/backebd-ecole

echo ""
echo "📊 COMPTAGE TOTAL DES DONNÉES:"
php artisan tinker --execute="
echo '👥 Élèves inscrits: ' . \App\Models\Inscription::where('statut', 'en_cours')->count();
echo '🏫 Classes: ' . \App\Models\Classe::count();
echo '📚 Matières: ' . \App\Models\Matiere::where('actif', true)->count();
echo '👨‍🏫 Enseignants: ' . \App\Models\Enseignant::count();
echo '📅 Périodes: ' . \App\Models\Periode::count();
echo '📝 Notes générées: ' . \App\Models\Note::count();
echo '📋 Bulletins générés: ' . \App\Models\Bulletin::count();
"

echo ""
echo "📈 STATISTIQUES DES BULLETINS:"
php artisan tinker --execute="
echo 'Moyenne générale: ' . round(\App\Models\Bulletin::avg('moyenne_generale'), 2) . '/20';
echo 'Meilleure moyenne: ' . \App\Models\Bulletin::max('moyenne_generale') . '/20';
echo 'Moins bonne moyenne: ' . \App\Models\Bulletin::min('moyenne_generale') . '/20';
"

echo ""
echo "🏆 RÉPARTITION PAR MENTION:"
php artisan tinker --execute="
\$total = \App\Models\Bulletin::count();
\$mentions = ['excellent' => 'Excellent', 'tres_bien' => 'Très bien', 'bien' => 'Bien', 'assez_bien' => 'Assez bien', 'passable' => 'Passable', 'insuffisant' => 'Insuffisant'];
foreach(\$mentions as \$key => \$label) {
    \$count = \App\Models\Bulletin::where('mention', \$key)->count();
    \$percent = \$total > 0 ? round((\$count / \$total) * 100, 1) : 0;
    echo \$label . ': ' . \$count . ' (' . \$percent . '%)';
}
"

echo ""
echo "📋 EXEMPLE DE BULLETIN:"
php artisan tinker --execute="
\$bulletin = \App\Models\Bulletin::with(['eleve.user', 'classe', 'periode'])->first();
if(\$bulletin) {
    echo '═══════════════════════════════════════';
    echo 'BULLETIN SCOLAIRE';
    echo '═══════════════════════════════════════';
    echo 'Élève: ' . \$bulletin->eleve->user->prenom . ' ' . \$bulletin->eleve->user->nom;
    echo 'Classe: ' . \$bulletin->classe->nom;
    echo 'Période: ' . \$bulletin->periode->nom;
    echo 'Moyenne générale: ' . \$bulletin->moyenne_generale . '/20';
    echo 'Rang: ' . \$bulletin->rang . '/' . \$bulletin->effectif_classe;
    echo 'Mention: ' . \$bulletin->mention_label;
    echo 'Observation: ' . \$bulletin->observation_conseil;
    echo '═══════════════════════════════════════';
}
"

echo ""
echo "✅ VÉRIFICATION TERMINÉE !"
echo ""
echo "🎉 Le système de gestion des notes et bulletins est opérationnel !"
echo "📊 Vous pouvez maintenant tester les fonctionnalités de:"
echo "   - Affichage des bulletins"
echo "   - Calculs de moyennes"
echo "   - Classements par classe"
echo "   - Export PDF"
echo "   - Tableaux de bord"
echo ""
