<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Classe;
use App\Models\Eleve;
use App\Models\Enseignant;
use App\Models\Inscription;
use App\Models\AnneeScolaire;
use App\Models\Note;
use App\Models\Bulletin;
use App\Models\ParentEleve;
use App\Models\Periode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function monTableauBord()
    {
        $user = auth()->user();
        
        // Rediriger selon le rôle de l'utilisateur
        switch($user->role) {
            case 'parent':
                return $this->statistiquesParent();
            case 'eleve':
                return $this->statistiquesEleve();
            case 'enseignant':
                return $this->statistiquesEnseignant();
            default:
                // Pour admin et autres rôles
                $anneeCourante = AnneeScolaire::where('actuelle', true)->first();

                $stats = [
                    'annee_scolaire' => $anneeCourante,
                    'totaux' => [
                        'eleves' => User::where('role', 'eleve')->where('actif', true)->count(),
                        'enseignants' => User::where('role', 'enseignant')->where('actif', true)->count(),
                        'parents' => User::where('role', 'parent')->where('actif', true)->count(),
                        'classes' => Classe::where('actif', true)
                            ->where('annee_scolaire_id', $anneeCourante?->id)
                            ->count(),
                    ],
                    'inscriptions' => [
                        'total' => Inscription::where('annee_scolaire_id', $anneeCourante?->id)
                            ->where('statut', 'en_cours')
                            ->count(),
                        'par_niveau' => $this->getInscriptionsParNiveau($anneeCourante?->id),
                        'par_sexe' => $this->getInscriptionsParSexe($anneeCourante?->id),
                    ],
                    'taux_remplissage' => $this->getTauxRemplissageClasses($anneeCourante?->id),
                    'derniers_inscrits' => $this->getDerniersInscrits(),
                    'activite_recente' => $this->getActiviteRecente(),
                ];

                return response()->json([
                    'success' => true,
                    'data' => $stats
                ]);
        }
    }

    protected function getInscriptionsParNiveau($anneeScolaireId)
    {
        return DB::table('inscriptions')
            ->join('classes', 'inscriptions.classe_id', '=', 'classes.id')
            ->join('niveaux', 'classes.niveau_id', '=', 'niveaux.id')
            ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)
            ->where('inscriptions.statut', 'en_cours')
            ->select('niveaux.nom', DB::raw('count(*) as total'))
            ->groupBy('niveaux.nom', 'niveaux.ordre')
            ->orderBy('niveaux.ordre')
            ->get();
    }

    protected function getInscriptionsParSexe($anneeScolaireId)
    {
        return DB::table('inscriptions')
            ->join('eleves', 'inscriptions.eleve_id', '=', 'eleves.id')
            ->join('users', 'eleves.user_id', '=', 'users.id')
            ->where('inscriptions.annee_scolaire_id', $anneeScolaireId)
            ->where('inscriptions.statut', 'en_cours')
            ->select('users.sexe', DB::raw('count(*) as total'))
            ->groupBy('users.sexe')
            ->get();
    }

    protected function getTauxRemplissageClasses($anneeScolaireId)
    {
        $classes = Classe::where('annee_scolaire_id', $anneeScolaireId)
            ->where('actif', true)
            ->with(['inscriptions' => function($q) {
                $q->where('statut', 'en_cours');
            }])
            ->get();

        $tauxTotal = 0;
        $capaciteTotale = 0;
        $effectifTotal = 0;

        $details = $classes->map(function($classe) use (&$capaciteTotale, &$effectifTotal) {
            $effectif = $classe->inscriptions->count();
            $capaciteTotale += $classe->capacite;
            $effectifTotal += $effectif;

            return [
                'classe' => $classe->nom,
                'niveau' => $classe->niveau->nom,
                'effectif' => $effectif,
                'capacite' => $classe->capacite,
                'taux' => round(($effectif / $classe->capacite) * 100, 2)
            ];
        });

        $tauxTotal = $capaciteTotale > 0 ? round(($effectifTotal / $capaciteTotale) * 100, 2) : 0;

        return [
            'taux_global' => $tauxTotal,
            'details' => $details
        ];
    }

    protected function getDerniersInscrits()
    {
        return Inscription::with(['eleve.user', 'classe'])
            ->where('statut', 'en_cours')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($inscription) {
                return [
                    'eleve' => $inscription->eleve->user->nom_complet,
                    'matricule' => $inscription->eleve->user->matricule,
                    'classe' => $inscription->classe->nom,
                    'date' => $inscription->created_at->format('d/m/Y H:i')
                ];
            });
    }

    protected function getActiviteRecente()
    {
        $activites = [];

        // Dernières notes saisies
        $dernieresNotes = Note::with(['eleve.user', 'matiere', 'enseignant.user'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($dernieresNotes as $note) {
            $activites[] = [
                'type' => 'note',
                'description' => "Note saisie en {$note->matiere->nom} pour {$note->eleve->user->nom_complet}",
                'par' => $note->enseignant->user->nom_complet,
                'date' => $note->updated_at
            ];
        }

        // Derniers bulletins générés
        $derniersBulletins = Bulletin::with(['eleve.user', 'periode'])
            ->whereNotNull('genere_le')
            ->orderBy('genere_le', 'desc')
            ->limit(5)
            ->get();

        foreach ($derniersBulletins as $bulletin) {
            $activites[] = [
                'type' => 'bulletin',
                'description' => "Bulletin généré pour {$bulletin->eleve->user->nom_complet} - {$bulletin->periode->nom}",
                'par' => 'Système',
                'date' => $bulletin->genere_le
            ];
        }

        // Trier par date
        usort($activites, function($a, $b) {
            return $b['date']->timestamp - $a['date']->timestamp;
        });

        return array_slice($activites, 0, 10);
    }

    public function statistiquesEnseignant()
    {
        $user = auth()->user();
        $enseignant = Enseignant::where('user_id', $user->id)->firstOrFail();
        $anneeCourante = AnneeScolaire::where('actuelle', true)->first();

        $stats = [
            'mes_classes' => $enseignant->classes()->distinct()->count(),
            'mes_matieres' => $enseignant->matieres()
                ->wherePivot('annee_scolaire_id', $anneeCourante?->id)
                ->count(),
            'total_eleves' => $this->getTotalElevesEnseignant($enseignant),
            'notes_saisies' => [
                'total' => $enseignant->notes()->count(),
                'cette_semaine' => $enseignant->notes()
                    ->where('updated_at', '>=', now()->subWeek())
                    ->count(),
            ],
            'classes_details' => $this->getClassesDetailsEnseignant($enseignant),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    protected function getTotalElevesEnseignant($enseignant)
    {
        return DB::table('enseignants_classes')
            ->join('inscriptions', 'enseignants_classes.classe_id', '=', 'inscriptions.classe_id')
            ->where('enseignants_classes.enseignant_id', $enseignant->id)
            ->where('inscriptions.statut', 'en_cours')
            ->distinct('inscriptions.eleve_id')
            ->count();
    }

    protected function getClassesDetailsEnseignant($enseignant)
    {
        return $enseignant->classes()
            ->with(['niveau', 'inscriptions' => function($q) {
                $q->where('statut', 'en_cours');
            }])
            ->get()
            ->map(function($classe) {
                return [
                    'classe' => $classe->nom,
                    'niveau' => $classe->niveau->nom,
                    'effectif' => $classe->inscriptions->count(),
                    'matiere' => \App\Models\Matiere::find($classe->pivot->matiere_id)->nom
                ];
            });
    }

    public function statistiquesEleve()
    {
        $user = auth()->user();
        $eleve = Eleve::where('user_id', $user->id)->firstOrFail();

        $inscription = $eleve->inscriptions()
            ->where('statut', 'en_cours')
            ->with('classe.niveau')
            ->first();

        $stats = [
            'classe_actuelle' => $inscription ? [
                'nom' => $inscription->classe->nom,
                'niveau' => $inscription->classe->niveau->nom,
                'effectif' => $inscription->classe->inscriptions()
                    ->where('statut', 'en_cours')
                    ->count()
            ] : null,
            'bulletins' => [
                'total' => $eleve->bulletins()->count(),
                'dernier' => $eleve->bulletins()
                    ->with('periode')
                    ->orderBy('created_at', 'desc')
                    ->first(),
            ],
            'moyennes' => $this->getMoyennesEleve($eleve),
            'progression' => $this->getProgressionEleve($eleve),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    protected function getMoyennesEleve($eleve)
    {
        $bulletins = $eleve->bulletins()
            ->with('periode')
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();

        return $bulletins->map(function($bulletin) {
            return [
                'periode' => $bulletin->periode->nom,
                'moyenne' => $bulletin->moyenne_generale,
                'rang' => $bulletin->rang . '/' . $bulletin->effectif_classe,
                'mention' => $bulletin->mention_label
            ];
        });
    }

    protected function getProgressionEleve($eleve)
    {
        $notes = $eleve->notes()
            ->with(['matiere', 'periode'])
            ->orderBy('periode_id')
            ->get()
            ->groupBy('matiere_id');

        $progression = [];

        foreach ($notes as $matiereId => $notesMatiere) {
            $matiere = $notesMatiere->first()->matiere;
            $moyennes = $notesMatiere->pluck('moyenne', 'periode_id');

            $progression[] = [
                'matiere' => $matiere->nom,
                'moyennes' => $moyennes,
                'evolution' => $this->calculerEvolution($moyennes->values()->toArray())
            ];
        }

        return $progression;
    }

    protected function calculerEvolution($moyennes)
    {
        if (count($moyennes) < 2) return 'stable';

        $derniere = end($moyennes);
        $avantDerniere = prev($moyennes);

        if ($derniere > $avantDerniere) return 'hausse';
        if ($derniere < $avantDerniere) return 'baisse';
        return 'stable';
    }

    public function statistiquesGenerales()
    {
        $anneeCourante = AnneeScolaire::where('actuelle', true)->first();

        $stats = [
            // Statistiques académiques globales
            'academicStats' => $this->getAcademicStats($anneeCourante),
            
            // Moyennes générales par classe
            'classAverages' => $this->getClassAverages($anneeCourante),
            
            // Statistiques des notes saisies
            'gradeStats' => $this->getGradeStats($anneeCourante),
            
            // Activité récente détaillée
            'recentActivity' => $this->getDetailedRecentActivity(),
            
            // Alertes et notifications
            'activeAlerts' => $this->getActiveAlerts($anneeCourante),
            
            // Nouvelles inscriptions
            'newEnrollments' => $this->getNewEnrollments($anneeCourante),
            
            // Bulletins générés
            'reportsGenerated' => $this->getGeneratedReports($anneeCourante),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    protected function getAcademicStats($anneeCourante)
    {
        if (!$anneeCourante) {
            return [
                'averageGeneral' => 'N/A',
                'successRate' => 0,
                'strugglingStudents' => 0,
                'newEnrollments' => 0,
                'reportsGenerated' => 0,
                'activeAlerts' => 0
            ];
        }

        // Moyenne générale de l'école
        $moyenneGenerale = DB::table('notes')
            ->join('inscriptions', 'notes.eleve_id', '=', 'inscriptions.eleve_id')
            ->where('inscriptions.annee_scolaire_id', $anneeCourante->id)
            ->where('inscriptions.statut', 'en_cours')
            ->avg('notes.note');

        // Taux de réussite (élèves avec moyenne >= 10)
        $totalEleves = Inscription::where('annee_scolaire_id', $anneeCourante->id)
            ->where('statut', 'en_cours')
            ->count();

        $elevesReussit = DB::table('notes')
            ->join('inscriptions', 'notes.eleve_id', '=', 'inscriptions.eleve_id')
            ->where('inscriptions.annee_scolaire_id', $anneeCourante->id)
            ->where('inscriptions.statut', 'en_cours')
            ->select('notes.eleve_id', DB::raw('AVG(notes.note) as moyenne'))
            ->groupBy('notes.eleve_id')
            ->havingRaw('AVG(notes.note) >= 10')
            ->count();

        $tauxReussite = $totalEleves > 0 ? round(($elevesReussit / $totalEleves) * 100, 2) : 0;

        // Élèves en difficulté (moyenne < 8)
        $elevesEnDifficulte = DB::table('notes')
        ->join('inscriptions', 'notes.eleve_id', '=', 'inscriptions.eleve_id')
        ->where('inscriptions.annee_scolaire_id', $anneeCourante->id)
        ->where('inscriptions.statut', 'en_cours')
        ->select('notes.eleve_id', DB::raw('AVG(notes.valeur) as moyenne')) // <-- ici
        ->groupBy('notes.eleve_id')
        ->havingRaw('AVG(notes.valeur) < 8') // <-- ici aussi
        ->count();
    

        return [
            'averageGeneral' => $moyenneGenerale ? round($moyenneGenerale, 2) : 'N/A',
            'successRate' => $tauxReussite,
            'strugglingStudents' => $elevesEnDifficulte,
            'newEnrollments' => $this->getNewEnrollmentsCount($anneeCourante),
            'reportsGenerated' => $this->getGeneratedReportsCount($anneeCourante),
            'activeAlerts' => $this->getActiveAlertsCount($anneeCourante)
        ];
    }

    protected function getClassAverages($anneeCourante)
    {
        if (!$anneeCourante) {
            return [];
        }

        $classes = Classe::where('annee_scolaire_id', $anneeCourante->id)
            ->where('actif', true)
            ->with(['niveau', 'inscriptions' => function($q) {
                $q->where('statut', 'en_cours');
            }])
            ->get();

        return $classes->map(function($classe) {
            // Calculer la moyenne de la classe
            $moyenneClasse = DB::table('notes')
                ->join('inscriptions', 'notes.eleve_id', '=', 'inscriptions.eleve_id')
                ->where('inscriptions.classe_id', $classe->id)
                ->where('inscriptions.statut', 'en_cours')
                ->avg('notes.note');

            // Calculer l'évolution (comparaison avec le trimestre précédent)
            $evolution = $this->getClassEvolution($classe->id);

            return [
                'nom' => $classe->nom,
                'niveau' => $classe->niveau->nom,
                'effectif' => $classe->inscriptions->count(),
                'moyenne' => $moyenneClasse ? round($moyenneClasse, 2) : null,
                'evolution' => $evolution
            ];
        })->toArray();
    }

    protected function getClassEvolution($classeId)
    {
        // Simplification : retourner une évolution simulée
        // Dans un vrai projet, on comparerait avec la période précédente
        $periodes = \App\Models\Periode::orderBy('ordre', 'desc')->limit(2)->get();
        
        if ($periodes->count() < 2) {
            return 0;
        }

        $currentPeriode = $periodes->first();
        $previousPeriode = $periodes->last();

        $currentAverage = DB::table('notes')
            ->join('inscriptions', 'notes.eleve_id', '=', 'inscriptions.eleve_id')
            ->where('inscriptions.classe_id', $classeId)
            ->where('notes.periode_id', $currentPeriode->id)
            ->avg('notes.note');

        $previousAverage = DB::table('notes')
            ->join('inscriptions', 'notes.eleve_id', '=', 'inscriptions.eleve_id')
            ->where('inscriptions.classe_id', $classeId)
            ->where('notes.periode_id', $previousPeriode->id)
            ->avg('notes.note');

        if (!$currentAverage || !$previousAverage) {
            return 0;
        }

        return round($currentAverage - $previousAverage, 2);
    }

    protected function getGradeStats($anneeCourante)
    {
        if (!$anneeCourante) {
            return [
                'today' => 0,
                'thisWeek' => 0,
                'thisMonth' => 0
            ];
        }

        return [
            'today' => Note::whereDate('created_at', today())->count(),
            'thisWeek' => Note::where('created_at', '>=', now()->subWeek())->count(),
            'thisMonth' => Note::where('created_at', '>=', now()->subMonth())->count(),
        ];
    }

    protected function getDetailedRecentActivity()
    {
        $activites = [];

        // Dernières notes saisies (plus détaillées)
        $dernieresNotes = Note::with(['eleve.user', 'matiere', 'enseignant.user', 'periode'])
            ->orderBy('created_at', 'desc')
            ->limit(8)
            ->get();

        foreach ($dernieresNotes as $note) {
            $activites[] = [
                'type' => 'note',
                'icon' => '📝',
                'title' => "Note saisie en {$note->matiere->nom}",
                'description' => "Note de {$note->note}/20 pour {$note->eleve->user->nom_complet}",
                'author' => $note->enseignant->user->nom_complet,
                'date' => $note->created_at,
                'periode' => $note->periode->nom ?? 'N/A'
            ];
        }

        // Derniers bulletins générés
        $derniersBulletins = Bulletin::with(['eleve.user', 'periode'])
            ->whereNotNull('genere_le')
            ->orderBy('genere_le', 'desc')
            ->limit(5)
            ->get();

        foreach ($derniersBulletins as $bulletin) {
            $activites[] = [
                'type' => 'bulletin',
                'icon' => '📋',
                'title' => "Bulletin généré",
                'description' => "{$bulletin->eleve->user->nom_complet} - {$bulletin->periode->nom}",
                'author' => 'Système',
                'date' => $bulletin->genere_le,
                'moyenne' => $bulletin->moyenne_generale ?? 'N/A'
            ];
        }

        // Nouvelles inscriptions
        $nouvellesInscriptions = Inscription::with(['eleve.user', 'classe'])
            ->where('created_at', '>=', now()->subDays(7))
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        foreach ($nouvellesInscriptions as $inscription) {
            $activites[] = [
                'type' => 'inscription',
                'icon' => '👤',
                'title' => "Nouvelle inscription",
                'description' => "{$inscription->eleve->user->nom_complet} en {$inscription->classe->nom}",
                'author' => 'Administration',
                'date' => $inscription->created_at,
                'classe' => $inscription->classe->nom
            ];
        }

        // Trier par date
        usort($activites, function($a, $b) {
            return $b['date']->timestamp - $a['date']->timestamp;
        });

        return array_slice($activites, 0, 15);
    }

    protected function getActiveAlerts($anneeCourante)
    {
        // Alertes pour élèves en difficulté, classes avec faible moyenne, etc.
        $alerts = [];

        if (!$anneeCourante) {
            return $alerts;
        }

        // Élèves avec moyenne très faible
        $elevesEnDanger = DB::table('notes')
            ->join('inscriptions', 'notes.eleve_id', '=', 'inscriptions.eleve_id')
            ->join('eleves', 'notes.eleve_id', '=', 'eleves.id')
            ->join('users', 'eleves.user_id', '=', 'users.id')
            ->where('inscriptions.annee_scolaire_id', $anneeCourante->id)
            ->where('inscriptions.statut', 'en_cours')
            ->select('users.nom_complet', 'notes.eleve_id', DB::raw('AVG(notes.note) as moyenne'))
            ->groupBy('notes.eleve_id', 'users.nom_complet')
            ->havingRaw('AVG(notes.note) < 5')
            ->get();

        foreach ($elevesEnDanger as $eleve) {
            $alerts[] = [
                'type' => 'critical',
                'title' => 'Élève en danger',
                'message' => "{$eleve->nom_complet} a une moyenne de {$eleve->moyenne}/20",
                'action' => 'Intervention urgente recommandée'
            ];
        }

        return $alerts;
    }

    protected function getActiveAlertsCount($anneeCourante)
    {
        if (!$anneeCourante) {
            return 0;
        }

        // Compter les élèves avec moyenne < 5
        $elevesEnDanger = DB::table('notes')
            ->join('inscriptions', 'notes.eleve_id', '=', 'inscriptions.eleve_id')
            ->where('inscriptions.annee_scolaire_id', $anneeCourante->id)
            ->where('inscriptions.statut', 'en_cours')
            ->select('notes.eleve_id', DB::raw('AVG(notes.note) as moyenne'))
            ->groupBy('notes.eleve_id')
            ->havingRaw('AVG(notes.note) < 5')
            ->count();

        return $elevesEnDanger;
    }

    protected function getNewEnrollmentsCount($anneeCourante)
    {
        if (!$anneeCourante) {
            return 0;
        }

        return Inscription::where('annee_scolaire_id', $anneeCourante->id)
            ->where('created_at', '>=', now()->subMonth())
            ->count();
    }

    protected function getGeneratedReportsCount($anneeCourante)
    {
        if (!$anneeCourante) {
            return 0;
        }

        return Bulletin::join('inscriptions', 'bulletins.eleve_id', '=', 'inscriptions.eleve_id')
            ->where('inscriptions.annee_scolaire_id', $anneeCourante->id)
            ->whereNotNull('bulletins.genere_le')
            ->where('bulletins.created_at', '>=', now()->subMonth())
            ->count();
    }

    protected function getNewEnrollments($anneeCourante)
    {
        if (!$anneeCourante) {
            return 0;
        }

        return Inscription::where('annee_scolaire_id', $anneeCourante->id)
            ->where('created_at', '>=', now()->subWeek())
            ->count();
    }

    protected function getGeneratedReports($anneeCourante)
    {
        if (!$anneeCourante) {
            return 0;
        }

        return Bulletin::join('inscriptions', 'bulletins.eleve_id', '=', 'inscriptions.eleve_id')
            ->where('inscriptions.annee_scolaire_id', $anneeCourante->id)
            ->whereNotNull('bulletins.genere_le')
            ->where('bulletins.created_at', '>=', now()->subWeek())
            ->count();
    }

    public function statistiquesParent()
    {
        try {
            $user = auth()->user();
            $parent = $user->parent;
            
            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Profil parent non trouvé.'
                ], 404);
            }

            $anneeCourante = AnneeScolaire::where('actuelle', true)->first();
            
            Log::info("🔍 Dashboard Parent Debug", [
                'parent_id' => $parent->id,
                'annee_courante' => $anneeCourante ? $anneeCourante->id : 'null',
                'annee_nom' => $anneeCourante ? $anneeCourante->nom : 'null'
            ]);
            
            // Récupérer tous les enfants du parent avec des conditions plus flexibles
            $enfants = $parent->enfants()
                ->with([
                    'user', 
                    // Inscription ACTIVE pour n'importe quelle année scolaire récente
                    'inscriptions' => function($q) use ($anneeCourante) {
                        // Si pas d'année courante, prendre les inscriptions récentes
                        if ($anneeCourante) {
                            $q->where('annee_scolaire_id', $anneeCourante->id);
                        } else {
                            // Fallback : inscriptions des 2 dernières années
                            $q->whereIn('annee_scolaire_id', 
                                AnneeScolaire::orderBy('id', 'desc')->limit(2)->pluck('id')
                            );
                        }
                        
                        // Statuts acceptables plus larges
                        $q->whereIn('statut', ['confirmee', 'en_cours', 'provisoire'])
                          ->with('classe.niveau')
                          ->orderBy('created_at', 'desc');
                    },
                    'bulletins' => function($q) use ($anneeCourante) {
                        if ($anneeCourante) {
                            $q->whereHas('periode', function($query) use ($anneeCourante) {
                                $query->where('annee_scolaire_id', $anneeCourante->id);
                            });
                        }
                        $q->with('periode')->orderBy('created_at', 'desc');
                    }
                ])
                ->get();

            // Debug des données chargées
            foreach ($enfants as $enfant) {
                Log::info("👤 Enfant Debug", [
                    'enfant_id' => $enfant->id,
                    'nom' => $enfant->user->prenom ?? 'Nom manquant',
                    'inscriptions_count' => $enfant->inscriptions->count(),
                    'inscriptions_statuts' => $enfant->inscriptions->pluck('statut')->toArray(),
                    'bulletins_count' => $enfant->bulletins->count()
                ]);
            }

            $stats = [
                'nombre_enfants' => $enfants->count(),
                'enfants_details' => $this->getEnfantsDetailsAmélioré($enfants),
                'moyennes_globales' => $this->getMoyennesGlobalesEnfants($enfants),
                'evolution_tendances' => $this->getEvolutionTendances($enfants),
                'alertes_scolaires' => $this->getAlertesScolaires($enfants),
                'prochains_bulletins' => $this->getProchainsBulletins($enfants),
                'statistiques_presence' => $this->getStatistiquesPresence($enfants),
                'performances_par_matiere' => $this->getPerformancesParMatiere($enfants),
                'comparaison_classe' => $this->getComparaisonClasse($enfants),
                'activite_recente' => $this->getActiviteRecenteEnfants($enfants)
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error("❌ Erreur dashboard parent", [
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des statistiques parent.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function getEnfantsDetailsAmélioré($enfants)
    {
        return $enfants->map(function($enfant) {
            // Prendre la première inscription disponible (plus flexible)
            $inscriptionActuelle = $enfant->inscriptions->first();
            $dernierBulletin = $enfant->bulletins->first();
            
            // Debug pour cet enfant spécifique
            Log::info("📋 Détails enfant", [
                'enfant_id' => $enfant->id,
                'inscription_trouvee' => $inscriptionActuelle ? 'OUI' : 'NON',
                'classe_id' => $inscriptionActuelle ? $inscriptionActuelle->classe_id ?? 'null' : 'pas d\'inscription',
                'classe_nom' => $inscriptionActuelle && $inscriptionActuelle->classe ? $inscriptionActuelle->classe->nom : 'classe manquante'
            ]);
            
            return [
                'id' => $enfant->id,
                'nom_complet' => ($enfant->user->prenom ?? '') . ' ' . ($enfant->user->nom ?? ''),
                'prenom' => $enfant->user->prenom ?? 'Prénom manquant',
                'nom' => $enfant->user->nom ?? 'Nom manquant',
                'classe' => $this->getClasseEnfant($inscriptionActuelle),
                'niveau' => $this->getNiveauEnfant($inscriptionActuelle),
                'moyenne_generale' => $dernierBulletin ? $dernierBulletin->moyenne_generale : null,
                'rang' => $dernierBulletin ? $dernierBulletin->rang : null,
                'effectif_classe' => $dernierBulletin ? $dernierBulletin->effectif_classe : null,
                'mention' => $dernierBulletin ? $this->getMention($dernierBulletin->moyenne_generale) : null,
                'derniere_periode' => $dernierBulletin && $dernierBulletin->periode ? $dernierBulletin->periode->nom : null,
                'evolution' => $this->getEvolutionEnfant($enfant),
                'photo' => $enfant->user->photo ? asset('storage/' . $enfant->user->photo) : null
            ];
        });
    }

    private function getClasseEnfant($inscription)
    {
        if (!$inscription) {
            return 'Aucune inscription';
        }
        
        if (!$inscription->classe) {
            return 'Classe non définie';
        }
        
        return $inscription->classe->nom;
    }

    private function getNiveauEnfant($inscription)
    {
        if (!$inscription || !$inscription->classe || !$inscription->classe->niveau) {
            return 'N/A';
        }
        
        return $inscription->classe->niveau->nom;
    }

    private function getMoyennesGlobalesEnfants($enfants)
    {
        $moyennes = [];
        $totalMoyenne = 0;
        $nombreBulletins = 0;

        foreach ($enfants as $enfant) {
            $dernierBulletin = $enfant->bulletins->first();
            if ($dernierBulletin && $dernierBulletin->moyenne_generale) {
                $moyennes[] = $dernierBulletin->moyenne_generale;
                $totalMoyenne += $dernierBulletin->moyenne_generale;
                $nombreBulletins++;
            }
        }

        return [
            'moyenne_famille' => $nombreBulletins > 0 ? round($totalMoyenne / $nombreBulletins, 2) : null,
            'enfants_reussit' => count(array_filter($moyennes, fn($m) => $m >= 10)),
            'enfants_difficulte' => count(array_filter($moyennes, fn($m) => $m < 8)),
            'meilleure_moyenne' => $moyennes ? max($moyennes) : null,
            'enfant_meilleur' => $this->getEnfantMeilleureMoyenne($enfants)
        ];
    }

    private function getEvolutionTendances($enfants)
    {
        $tendances = [];
        
        foreach ($enfants as $enfant) {
            $bulletins = $enfant->bulletins->take(3); // 3 derniers bulletins
            
            if ($bulletins->count() >= 2) {
                $dernierBulletin = $bulletins->first();
                $precedentBulletin = $bulletins->skip(1)->first();
                
                $evolution = $dernierBulletin->moyenne_generale - $precedentBulletin->moyenne_generale;
                
                $tendances[] = [
                    'enfant_id' => $enfant->id,
                    'enfant_nom' => $enfant->user->prenom,
                    'evolution' => round($evolution, 2),
                    'tendance' => $evolution > 0.5 ? 'hausse' : ($evolution < -0.5 ? 'baisse' : 'stable'),
                    'moyenne_actuelle' => $dernierBulletin->moyenne_generale,
                    'moyenne_precedente' => $precedentBulletin->moyenne_generale
                ];
            }
        }

        return $tendances;
    }

    private function getAlertesScolaires($enfants)
    {
        $alertes = [];

        foreach ($enfants as $enfant) {
            $dernierBulletin = $enfant->bulletins->first();
            
            if ($dernierBulletin) {
                // Alerte moyenne faible
                if ($dernierBulletin->moyenne_generale < 8) {
                    $alertes[] = [
                        'type' => 'critique',
                        'enfant' => $enfant->user->prenom,
                        'message' => 'Moyenne générale très faible (' . $dernierBulletin->moyenne_generale . '/20)',
                        'action_recommandee' => 'Renforcement pédagogique nécessaire',
                        'priorite' => 'haute'
                    ];
                }
                
                // Alerte rang faible
                if ($dernierBulletin->rang && $dernierBulletin->effectif_classe) {
                    $pourcentageRang = ($dernierBulletin->rang / $dernierBulletin->effectif_classe) * 100;
                    if ($pourcentageRang > 80) {
                        $alertes[] = [
                            'type' => 'attention',
                            'enfant' => $enfant->user->prenom,
                            'message' => 'Classement dans les derniers de la classe',
                            'action_recommandee' => 'Suivi personnalisé recommandé',
                            'priorite' => 'moyenne'
                        ];
                    }
                }
            }

            // Vérifier les notes récentes critiques
            $notesCritiques = Note::where('eleve_id', $enfant->id)
                ->where('moyenne', '<', 5)
                ->where('created_at', '>=', now()->subWeeks(2))
                ->with('matiere')
                ->get();

            foreach ($notesCritiques as $note) {
                $alertes[] = [
                    'type' => 'urgent',
                    'enfant' => $enfant->user->prenom,
                    'message' => 'Note très faible en ' . $note->matiere->nom . ' (' . $note->moyenne . '/20)',
                    'action_recommandee' => 'Contact avec l\'enseignant recommandé',
                    'priorite' => 'haute'
                ];
            }
        }

        return $alertes;
    }

    private function getProchainsBulletins($enfants)
    {
        $anneeCourante = AnneeScolaire::where('actuelle', true)->first();
        
        if (!$anneeCourante) {
            return [];
        }

        $prochainesPeriodes = \App\Models\Periode::where('annee_scolaire_id', $anneeCourante->id)
            ->where('date_fin', '>', now())
            ->orderBy('date_fin')
            ->get();

        $prochainsBulletins = [];

        foreach ($enfants as $enfant) {
            foreach ($prochainesPeriodes as $periode) {
                $bulletinExiste = $enfant->bulletins()
                    ->where('periode_id', $periode->id)
                    ->exists();

                if (!$bulletinExiste) {
                    $prochainsBulletins[] = [
                        'enfant' => $enfant->user->prenom,
                        'periode' => $periode->nom,
                        'date_prevue' => $periode->date_fin,
                        'jours_restants' => now()->diffInDays($periode->date_fin, false)
                    ];
                    break; // Premier bulletin manquant seulement
                }
            }
        }

        return $prochainsBulletins;
    }

    private function getStatistiquesPresence($enfants)
    {
        // Simulation - dans un vrai système, il faudrait une table absences
        return [
            'total_jours_ecole' => 180, // Exemple
            'jours_presence_moyenne' => 165,
            'taux_presence_famille' => 91.7,
            'enfants_assidus' => $enfants->count(),
            'alertes_absence' => 0
        ];
    }

    private function getPerformancesParMatiere($enfants)
    {
        $performancesParMatiere = [];

        foreach ($enfants as $enfant) {
            $notesParMatiere = Note::where('eleve_id', $enfant->id)
                ->with('matiere')
                ->where('created_at', '>=', now()->subMonths(3))
                ->get()
                ->groupBy('matiere_id');

            foreach ($notesParMatiere as $matiereId => $notes) {
                $matiere = $notes->first()->matiere;
                $moyenneMatiere = $notes->avg('moyenne');

                $performancesParMatiere[] = [
                    'enfant' => $enfant->user->prenom,
                    'matiere' => $matiere->nom,
                    'moyenne' => round($moyenneMatiere, 2),
                    'nombre_notes' => $notes->count(),
                    'tendance' => $this->getTendanceMatiere($notes)
                ];
            }
        }

        return collect($performancesParMatiere)
            ->groupBy('matiere')
            ->map(function($matiereData, $matiere) {
                return [
                    'matiere' => $matiere,
                    'moyenne_famille' => round($matiereData->avg('moyenne'), 2),
                    'details_enfants' => $matiereData->toArray()
                ];
            })
            ->values();
    }

    private function getComparaisonClasse($enfants)
    {
        $comparaisons = [];

        foreach ($enfants as $enfant) {
            $inscriptionActuelle = $enfant->inscriptions->first();
            $dernierBulletin = $enfant->bulletins->first();

            if ($inscriptionActuelle && $dernierBulletin) {
                // Calculer la moyenne de la classe
                $moyenneClasse = DB::table('bulletins')
                    ->join('inscriptions', 'bulletins.eleve_id', '=', 'inscriptions.eleve_id')
                    ->where('inscriptions.classe_id', $inscriptionActuelle->classe_id)
                    ->where('bulletins.periode_id', $dernierBulletin->periode_id)
                    ->avg('bulletins.moyenne_generale');

                $comparaisons[] = [
                    'enfant' => $enfant->user->prenom,
                    'classe' => $inscriptionActuelle->classe->nom,
                    'moyenne_enfant' => $dernierBulletin->moyenne_generale,
                    'moyenne_classe' => round($moyenneClasse, 2),
                    'difference' => round($dernierBulletin->moyenne_generale - $moyenneClasse, 2),
                    'rang' => $dernierBulletin->rang,
                    'effectif' => $dernierBulletin->effectif_classe,
                    'position' => $this->getPositionRelative($dernierBulletin->rang, $dernierBulletin->effectif_classe)
                ];
            }
        }

        return $comparaisons;
    }

    private function getActiviteRecenteEnfants($enfants)
    {
        $activites = [];

        foreach ($enfants as $enfant) {
            // Dernières notes
            $dernieresNotes = Note::where('eleve_id', $enfant->id)
                ->with(['matiere', 'enseignant.user'])
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();

            foreach ($dernieresNotes as $note) {
                $activites[] = [
                    'type' => 'note',
                    'enfant' => $enfant->user->prenom,
                    'description' => 'Note en ' . $note->matiere->nom . ': ' . $note->moyenne . '/20',
                    'date' => $note->created_at,
                    'enseignant' => $note->enseignant ? $note->enseignant->user->nom_complet : 'N/A'
                ];
            }

            // Derniers bulletins
            $derniersBulletins = $enfant->bulletins->take(2);
            foreach ($derniersBulletins as $bulletin) {
                $activites[] = [
                    'type' => 'bulletin',
                    'enfant' => $enfant->user->prenom,
                    'description' => 'Bulletin ' . $bulletin->periode->nom . ' généré',
                    'date' => $bulletin->created_at,
                    'moyenne' => $bulletin->moyenne_generale
                ];
            }
        }

        // Trier par date
        usort($activites, function($a, $b) {
            return $b['date']->timestamp - $a['date']->timestamp;
        });

        return array_slice($activites, 0, 10);
    }

    // Méthodes helper
    private function getMention($moyenne)
    {
        if ($moyenne >= 16) return 'Très bien';
        if ($moyenne >= 14) return 'Bien';
        if ($moyenne >= 12) return 'Assez bien';
        if ($moyenne >= 10) return 'Passable';
        return 'Insuffisant';
    }

    private function getEvolutionEnfant($enfant)
    {
        $bulletins = $enfant->bulletins->take(2);
        
        if ($bulletins->count() < 2) {
            return 0;
        }

        $dernierBulletin = $bulletins->first();
        $precedentBulletin = $bulletins->skip(1)->first();

        return round($dernierBulletin->moyenne_generale - $precedentBulletin->moyenne_generale, 2);
    }

    private function getEnfantMeilleureMoyenne($enfants)
    {
        $meilleurEnfant = null;
        $meilleureMoyenne = 0;

        foreach ($enfants as $enfant) {
            $dernierBulletin = $enfant->bulletins->first();
            if ($dernierBulletin && $dernierBulletin->moyenne_generale > $meilleureMoyenne) {
                $meilleureMoyenne = $dernierBulletin->moyenne_generale;
                $meilleurEnfant = $enfant->user->prenom;
            }
        }

        return $meilleurEnfant;
    }

    private function getTendanceMatiere($notes)
    {
        if ($notes->count() < 2) return 'stable';

        $notesOrdered = $notes->sortBy('created_at');
        $premiere = $notesOrdered->first()->moyenne;
        $derniere = $notesOrdered->last()->moyenne;

        $diff = $derniere - $premiere;
        
        if ($diff > 1) return 'hausse';
        if ($diff < -1) return 'baisse';
        return 'stable';
    }

    private function getPositionRelative($rang, $effectif)
    {
        $pourcentage = ($rang / $effectif) * 100;
        
        if ($pourcentage <= 25) return 'excellent';
        if ($pourcentage <= 50) return 'bon';
        if ($pourcentage <= 75) return 'moyen';
        return 'faible';
    }
}
