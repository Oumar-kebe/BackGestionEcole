<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use App\Models\ParentEleve;
use App\Models\Eleve;
use Illuminate\Support\Facades\Hash;

echo "🔧 Création d'un parent de test...\n";

// Vérifier s'il y a déjà un parent de test
$parentTest = User::where('email', 'parent.test@ecole.com')->first();

if ($parentTest) {
    echo "✅ Parent de test existe déjà: {$parentTest->email}\n";
} else {
    echo "📝 Création d'un nouveau parent de test...\n";
    
    $parentTest = User::create([
        'name' => 'Parent Test',
        'nom' => 'Test',
        'prenom' => 'Parent',
        'email' => 'parent.test@ecole.com',
        'password' => Hash::make('test123'),
        'role' => 'parent',
        'sexe' => 'M',
        'telephone' => '77 999 99 99',
        'adresse' => 'Dakar, Sénégal',
        'matricule' => 'PAR2025TEST',
        'actif' => true,
    ]);
    
    echo "✅ Utilisateur parent créé: ID {$parentTest->id}\n";
}

// Vérifier/créer le profil parent
$profilParent = $parentTest->parent;

if (!$profilParent) {
    echo "📝 Création du profil parent...\n";
    
    $profilParent = ParentEleve::create([
        'user_id' => $parentTest->id,
        'profession' => 'Développeur',
        'lieu_travail' => 'Dakar',
        'telephone_bureau' => '33 123 45 67',
    ]);
    
    echo "✅ Profil parent créé: ID {$profilParent->id}\n";
} else {
    echo "✅ Profil parent existe: ID {$profilParent->id}\n";
}

// Vérifier s'il a des enfants
$enfantsCount = $profilParent->enfants()->count();
echo "👨‍👩‍👧‍👦 Nombre d'enfants: {$enfantsCount}\n";

if ($enfantsCount == 0) {
    echo "📝 Liaison avec un élève existant...\n";
    
    // Prendre le premier élève disponible
    $eleve = Eleve::with('user')->first();
    
    if ($eleve) {
        // Lier le parent à l'élève
        $profilParent->enfants()->attach($eleve->id, [
            'lien_parente' => 'pere'
        ]);
        
        echo "✅ Enfant lié: {$eleve->user->prenom} {$eleve->user->nom} (ID: {$eleve->id})\n";
    } else {
        echo "❌ Aucun élève trouvé pour créer la liaison\n";
    }
} else {
    $enfants = $profilParent->enfants()->with('user')->get();
    echo "👶 Enfants:\n";
    foreach ($enfants as $enfant) {
        echo "  - {$enfant->user->prenom} {$enfant->user->nom} (ID: {$enfant->id})\n";
    }
}

echo "\n🎯 Informations de connexion:\n";
echo "Email: parent.test@ecole.com\n";
echo "Mot de passe: test123\n";
echo "\n✅ Configuration terminée!\n";
