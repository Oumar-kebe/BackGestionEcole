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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function statistiquesGenerales()
    {
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

    public function statistiquesParent()
    {
        $user = auth()->user();
        $parent = \App\Models\ParentEleve::where('user_id', $user->id)->firstOrFail();

        $stats = [
            'nombre_enfants' => $parent->enfants()->count(),
            'enfants' => $parent->enfants()
                ->with(['user', 'inscriptions.classe.niveau'])
                ->get()
                ->map(function($enfant) {
                    $inscription = $enfant->inscriptions()
                        ->where('statut', 'en_cours')
                        ->first();

                    return [
                        'nom' => $enfant->user->nom_complet,
                        'matricule' => $enfant->user->matricule,
                        'classe' => $inscription?->classe?->nom,
                        'niveau' => $inscription?->classe?->niveau?->nom,
                        'dernier_bulletin' => $enfant->bulletins()
                            ->orderBy('created_at', 'desc')
                            ->first(),
                    ];
                }),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
