<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\AnneeScolaireController;
use App\Http\Controllers\Api\NiveauController;
use App\Http\Controllers\Api\ClasseController;
use App\Http\Controllers\Api\MatiereController;
use App\Http\Controllers\Api\EleveController;
use App\Http\Controllers\Api\ParentController;
use App\Http\Controllers\Api\EnseignantController;
use App\Http\Controllers\Api\InscriptionController;
use App\Http\Controllers\Api\NoteController;
use App\Http\Controllers\Api\BulletinController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\PeriodeController;
use App\Http\Controllers\Api\EmploiTempsController;
use App\Http\Controllers\Api\DebugController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Routes publiques (authentification)
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);


    Route::middleware('jwt.auth')->group(function () {

        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
    });
});

// Routes protégées par JWT
Route::middleware(['jwt.auth', 'active'])->group(function () {

    // Routes accessibles à tous les utilisateurs connectés
    Route::get('dashboard/mon-tableau-bord', [DashboardController::class, 'monTableauBord']);
    Route::get('profil', [AuthController::class, 'me']);

    // Routes de debug (temporaires)
    Route::get('debug/user-info', [DebugController::class, 'userInfo']);
    Route::get('debug/parent-enfants', [DebugController::class, 'parentEnfants']);

    // Routes pour l'administrateur uniquement
    Route::middleware(['role:administrateur'])->group(function () {

        // Gestion des utilisateurs
        Route::apiResource('users', UserController::class);
        Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus']);
        Route::post('users/{user}/reset-password', [UserController::class, 'resetPassword']);

        // Gestion des années scolaires
        Route::apiResource('annees-scolaires', AnneeScolaireController::class);
        Route::post('annees-scolaires/{anneeScolaire}/set-actuelle', [AnneeScolaireController::class, 'setActuelle']);
        Route::get('annees-scolaires/{anneeScolaire}/statistiques', [AnneeScolaireController::class, 'statistiques']);

        // Gestion des niveaux
        Route::apiResource('niveaux', NiveauController::class);
        Route::get('niveaux/{niveau}/matieres', [NiveauController::class, 'matieres']);
        Route::get('niveaux/{niveau}/classes', [NiveauController::class, 'classes']);

        // Gestion des classes
        Route::apiResource('classes', ClasseController::class);
        Route::get('classes/{classe}/eleves', [ClasseController::class, 'eleves']);
        Route::get('classes/{classe}/enseignants', [ClasseController::class, 'enseignants']);
        Route::post('classes/{classe}/assigner-enseignant', [ClasseController::class, 'assignerEnseignant']);
        Route::post('classes/{classe}/retirer-enseignant', [ClasseController::class, 'retirerEnseignant']);

        // Gestion des matières
        Route::apiResource('matieres', MatiereController::class);
        Route::post('matieres/{matiere}/toggle-status', [MatiereController::class, 'toggleStatus']);
        Route::get('matieres/{matiere}/enseignants', [MatiereController::class, 'enseignants']);
        Route::post('matieres/{matiere}/assigner-enseignant', [MatiereController::class, 'assignerEnseignant']);
        Route::delete('matieres/{matiere}/detacher-enseignant', [MatiereController::class, 'detacherEnseignant']);

        // Gestion des élèves
        Route::apiResource('eleves', EleveController::class);
        Route::post('eleves/{eleve}/changer-classe', [EleveController::class, 'changerClasse']);
        Route::get('eleves/{eleve}/documents', [DocumentController::class, 'documentsEleve']);
        Route::post('eleves/{eleve}/documents', [DocumentController::class, 'uploadDocumentEleve']);

        // Gestion des parents
        Route::apiResource('parents', ParentController::class)->except(['store', 'destroy']);
        Route::get('parents/{parent}/enfants', [ParentController::class, 'enfants']);
        Route::post('parents/{parent}/ajouter-enfant', [ParentController::class, 'ajouterEnfant']);
        Route::delete('parents/{parent}/retirer-enfant/{eleve}', [ParentController::class, 'retirerEnfant']);

        // Gestion des enseignants
        Route::apiResource('enseignants', EnseignantController::class);
        Route::post('enseignants/{enseignant}/assigner-matiere', [EnseignantController::class, 'assignerMatiere']);
        Route::post('enseignants/{enseignant}/retirer-matiere', [EnseignantController::class, 'retirerMatiere']);
        Route::post('enseignants/{enseignant}/assigner-classe', [EnseignantController::class, 'assignerClasse']);

        // Gestion des inscriptions
        Route::apiResource('inscriptions', InscriptionController::class);
        Route::post('inscriptions/{inscription}/terminer', [InscriptionController::class, 'terminer']);
        Route::get('inscriptions/statistiques/classe/{classe}', [InscriptionController::class, 'statistiquesClasse']);

        // Gestion des périodes
        Route::apiResource('periodes', PeriodeController::class);
        Route::post('periodes/{periode}/set-actuelle', [PeriodeController::class, 'setActuelle']);
        Route::get('periodes/{periode}/statistiques', [PeriodeController::class, 'statistiques']);

        // Gestion des emplois du temps
        Route::apiResource('emploi-temps', EmploiTempsController::class);
        Route::get('emploi-temps-semaine', [EmploiTempsController::class, 'emploiSemaine']);

        // Gestion des emplois du temps
        Route::apiResource('emploi-temps', EmploiTempsController::class);
        Route::get('emploi-temps-semaine', [EmploiTempsController::class, 'emploiSemaine']);

        // Génération des bulletins
        Route::post('bulletins/generer', [BulletinController::class, 'generer']);
        // Route::apiResource('admin/bulletins', BulletinController::class);

        Route::post('bulletins/{bulletin}/observation-conseil', [BulletinController::class, 'observationConseil']);
        Route::get('bulletins/telecharger-groupe', [BulletinController::class, 'telechargerGroupe']);

        // Dashboard administrateur
        Route::get('dashboard/statistiques-generales', [DashboardController::class, 'statistiquesGenerales']);

        // Documents
        Route::apiResource('documents', DocumentController::class);
    });

    // Routes pour les enseignants
    Route::middleware(['role:enseignant'])->group(function () {
        // Mes classes et matières
        Route::get('enseignant/mes-classes', [EnseignantController::class, 'mesClasses']);

        // Mon emploi du temps
        Route::get('enseignant/mon-emploi-temps', [EmploiTempsController::class, 'emploiSemaine']);

        // Saisie des notes
        Route::get('notes', [NoteController::class, 'index']);
        Route::post('notes', [NoteController::class, 'store']);
        Route::put('notes/{note}', [NoteController::class, 'update']);
        Route::get('notes/saisie-par-classe', [NoteController::class, 'saisieParClasse']);
        Route::post('notes/saisie-groupee', [NoteController::class, 'saisieGroupee']);
        Route::get('notes/statistiques-classe', [NoteController::class, 'statistiquesClasse']);

        // Dashboard enseignant
        Route::get('dashboard/statistiques-enseignant', [DashboardController::class, 'statistiquesEnseignant']);
    });

    // Routes pour les élèves
    Route::middleware(['role:eleve'])->group(function () {
        // Mes notes et bulletins
        Route::get('eleve/mes-notes', [NoteController::class, 'mesNotes']);
        Route::get('eleve/mes-bulletins', [BulletinController::class, 'mesBulletins']);

        // Mon emploi du temps
        Route::get('eleve/mon-emploi-temps', [EmploiTempsController::class, 'emploiSemaine']);

        // Dashboard élève
        Route::get('dashboard/statistiques-eleve', [DashboardController::class, 'statistiquesEleve']);
    });

    // Routes pour les parents
    Route::middleware(['role:parent'])->group(function () {
        // Mes enfants
        Route::get('parent/mes-enfants', [ParentController::class, 'mesEnfants']);
        Route::get('parent/eleve/{eleveId}/bulletin/{trimestreId}', [ParentController::class, 'voirBulletin']);

        // Route::get('parent/eleve_id/{eleve_id}/bulletin', [ParentController::class, 'voirBulletin']);
            // ->middleware('parent.access');
        Route::get('parent/enfant/{eleve}/notes', [NoteController::class, 'notesEleve'])
            ->middleware('parent.access');

        // Emploi du temps de mes enfants
        Route::get('parent/enfant/{eleve}/emploi-temps', [EmploiTempsController::class, 'emploiSemaine'])
            ->middleware('parent.access');

        // Dashboard parent
        Route::get('dashboard/statistiques-parent', [DashboardController::class, 'statistiquesParent']);
    });

    // Routes communes pour consultation (admin, enseignant, élève concerné, parent concerné)
    Route::group([], function () {
        // Notes
        Route::get('notes', [NoteController::class, 'index'])
            ->middleware('role:administrateur,enseignant');
        Route::get('notes/eleve/{eleve}', [NoteController::class, 'notesEleve'])
            ->middleware('role:administrateur,enseignant');

        // Bulletins
        Route::get('bulletins', [BulletinController::class, 'index'])
            ->middleware('role:administrateur');
        Route::get('bulletins/{bulletin}', [BulletinController::class, 'show']);
        Route::get('bulletins/{bulletin}/telecharger', [BulletinController::class, 'telecharger']);

        // Périodes (consultation)
        Route::get('periodes', [PeriodeController::class, 'index']);
        Route::get('periodes/{periode}', [PeriodeController::class, 'show']);
    });
});

// Route de test
Route::get('/test', function () {
    return response()->json([
        'message' => 'API GestionEcole fonctionne!',
        'version' => '1.0'
    ]);
});

// Route de test JWT
Route::get('/test-jwt', function () {
    try {
        $user = auth('api')->user();
        return response()->json([
            'success' => true,
            'message' => 'JWT fonctionne!',
            'user' => $user ? $user->only(['id', 'name', 'email', 'role']) : null
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Erreur JWT: ' . $e->getMessage()
        ], 401);
    }
})->middleware('jwt.auth');
