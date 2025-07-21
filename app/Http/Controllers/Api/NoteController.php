<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Note;
use App\Models\Enseignant;
use App\Models\Classe;
use App\Models\Periode;
use App\Models\Eleve;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class NoteController extends Controller
{
    public function index(Request $request)
    {
        $query = Note::with(['eleve.user', 'matiere', 'periode', 'enseignant.user']);

        if ($request->has('classe_id')) {
            $query->whereHas('eleve.inscriptions', function($q) use ($request) {
                $q->where('classe_id', $request->classe_id)
                    ->where('statut', 'en_cours');
            });
        }

        if ($request->has('matiere_id')) {
            $query->where('matiere_id', $request->matiere_id);
        }

        if ($request->has('periode_id')) {
            $query->where('periode_id', $request->periode_id);
        }

        if ($request->has('enseignant_id')) {
            $query->where('enseignant_id', $request->enseignant_id);
        }

        $notes = $query->get();

        return response()->json([
            'success' => true,
            'data' => $notes
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'eleve_id' => 'required|exists:eleves,id',
            'matiere_id' => 'required|exists:matieres,id',
            'periode_id' => 'required|exists:periodes,id',
            'note_devoir1' => 'nullable|numeric|min:0|max:20',
            'note_devoir2' => 'nullable|numeric|min:0|max:20',
            'note_composition' => 'nullable|numeric|min:0|max:20',
        ]);

        // Vérifier que l'enseignant connecté enseigne cette matière
        $user = auth()->user();
        $enseignant = Enseignant::where('user_id', $user->id)->first();

        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas enseignant'
            ], 403);
        }

        // Vérifier que l'enseignant enseigne cette matière
        $periode = Periode::find($request->periode_id);
        if (!$enseignant->enseigneMatiere($request->matiere_id, $periode->annee_scolaire_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'enseignez pas cette matière'
            ], 403);
        }

        // Vérifier ou créer la note
        $note = Note::updateOrCreate(
            [
                'eleve_id' => $request->eleve_id,
                'matiere_id' => $request->matiere_id,
                'periode_id' => $request->periode_id,
            ],
            [
                'enseignant_id' => $enseignant->id,
                'note_devoir1' => $request->note_devoir1,
                'note_devoir2' => $request->note_devoir2,
                'note_composition' => $request->note_composition,
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Note enregistrée avec succès',
            'data' => $note->load(['eleve.user', 'matiere'])
        ]);
    }

    public function update(Request $request, $id)
    {
        $note = Note::findOrFail($id);

        $request->validate([
            'note_devoir1' => 'nullable|numeric|min:0|max:20',
            'note_devoir2' => 'nullable|numeric|min:0|max:20',
            'note_composition' => 'nullable|numeric|min:0|max:20',
        ]);

        // Vérifier que l'enseignant connecté est celui qui a saisi la note
        $user = auth()->user();
        $enseignant = Enseignant::where('user_id', $user->id)->first();

        if (!$enseignant || $note->enseignant_id !== $enseignant->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas autorisé à modifier cette note'
            ], 403);
        }

        $note->update($request->only(['note_devoir1', 'note_devoir2', 'note_composition']));

        return response()->json([
            'success' => true,
            'message' => 'Note mise à jour avec succès',
            'data' => $note
        ]);
    }

    public function saisieParClasse(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'matiere_id' => 'required|exists:matieres,id',
            'periode_id' => 'required|exists:periodes,id',
        ]);

        // Récupérer tous les élèves de la classe
        $eleves = Eleve::whereHas('inscriptions', function($q) use ($request) {
            $q->where('classe_id', $request->classe_id)
                ->where('statut', 'en_cours');
        })->with('user')->get();

        // Récupérer les notes existantes
        $notesExistantes = Note::where('matiere_id', $request->matiere_id)
            ->where('periode_id', $request->periode_id)
            ->whereIn('eleve_id', $eleves->pluck('id'))
            ->get()
            ->keyBy('eleve_id');

        // Préparer la liste des élèves avec leurs notes
        $listeEleves = $eleves->map(function($eleve) use ($notesExistantes) {
            $note = $notesExistantes->get($eleve->id);

            return [
                'eleve' => $eleve,
                'note' => $note ?? [
                        'note_devoir1' => null,
                        'note_devoir2' => null,
                        'note_composition' => null,
                        'moyenne' => null,
                        'appreciation' => null
                    ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $listeEleves
        ]);
    }

    public function saisieGroupee(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'matiere_id' => 'required|exists:matieres,id',
            'periode_id' => 'required|exists:periodes,id',
            'notes' => 'required|array',
            'notes.*.eleve_id' => 'required|exists:eleves,id',
            'notes.*.note_devoir1' => 'nullable|numeric|min:0|max:20',
            'notes.*.note_devoir2' => 'nullable|numeric|min:0|max:20',
            'notes.*.note_composition' => 'nullable|numeric|min:0|max:20',
        ]);

        // Vérifier l'autorisation de l'enseignant
        $user = auth()->user();
        $enseignant = Enseignant::where('user_id', $user->id)->first();

        if (!$enseignant) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'êtes pas enseignant'
            ], 403);
        }

        // Vérifier que l'enseignant enseigne cette matière à cette classe
        if (!$enseignant->enseigneClasse($request->classe_id, $request->matiere_id)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'enseignez pas cette matière à cette classe'
            ], 403);
        }

        DB::beginTransaction();

        try {
            $notesCreees = [];

            foreach ($request->notes as $noteData) {
                $note = Note::updateOrCreate(
                    [
                        'eleve_id' => $noteData['eleve_id'],
                        'matiere_id' => $request->matiere_id,
                        'periode_id' => $request->periode_id,
                    ],
                    [
                        'enseignant_id' => $enseignant->id,
                        'note_devoir1' => $noteData['note_devoir1'] ?? null,
                        'note_devoir2' => $noteData['note_devoir2'] ?? null,
                        'note_composition' => $noteData['note_composition'] ?? null,
                    ]
                );

                $notesCreees[] = $note;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($notesCreees) . ' notes enregistrées avec succès',
                'data' => $notesCreees
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement des notes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function mesNotes()
    {
        $user = auth()->user();
        $eleve = Eleve::where('user_id', $user->id)->firstOrFail();

        $notes = $eleve->notes()
            ->with(['matiere', 'periode', 'enseignant.user'])
            ->orderBy('periode_id')
            ->get();

        // Grouper par période
        $notesParPeriode = $notes->groupBy('periode_id')->map(function($notesPeriode) {
            $periode = $notesPeriode->first()->periode;
            $moyenneGenerale = $notesPeriode->avg('moyenne');

            return [
                'periode' => $periode,
                'notes' => $notesPeriode,
                'moyenne_generale' => round($moyenneGenerale, 2)
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $notesParPeriode
        ]);
    }

    public function notesEleve($eleveId)
    {
        // Vérifier l'autorisation (parent ou admin)
        $user = auth()->user();

        if ($user->role === 'parent') {
            $parent = \App\Models\ParentEleve::where('user_id', $user->id)->first();
            if (!$parent || !$parent->peutVoirEleve($eleveId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous n\'êtes pas autorisé à consulter ces notes'
                ], 403);
            }
        }

        $eleve = Eleve::findOrFail($eleveId);
        $notes = $eleve->notes()
            ->with(['matiere', 'periode', 'enseignant.user'])
            ->orderBy('periode_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $notes
        ]);
    }

    public function statistiquesClasse(Request $request)
    {
        $request->validate([
            'classe_id' => 'required|exists:classes,id',
            'periode_id' => 'required|exists:periodes,id',
        ]);

        $classe = Classe::find($request->classe_id);

        // Récupérer tous les élèves de la classe
        $eleveIds = $classe->inscriptions()
            ->where('statut', 'en_cours')
            ->pluck('eleve_id');

        // Statistiques par matière
        $matieres = $classe->niveau->matieres()->where('actif', true)->get();

        $stats = $matieres->map(function($matiere) use ($eleveIds, $request) {
            $notes = Note::where('matiere_id', $matiere->id)
                ->where('periode_id', $request->periode_id)
                ->whereIn('eleve_id', $eleveIds)
                ->get();

            return [
                'matiere' => $matiere,
                'moyenne_classe' => $notes->avg('moyenne'),
                'note_max' => $notes->max('moyenne'),
                'note_min' => $notes->min('moyenne'),
                'nombre_notes' => $notes->count(),
                'repartition' => [
                    'excellent' => $notes->where('moyenne', '>=', 18)->count(),
                    'tres_bien' => $notes->whereBetween('moyenne', [16, 17.99])->count(),
                    'bien' => $notes->whereBetween('moyenne', [14, 15.99])->count(),
                    'assez_bien' => $notes->whereBetween('moyenne', [12, 13.99])->count(),
                    'passable' => $notes->whereBetween('moyenne', [10, 11.99])->count(),
                    'insuffisant' => $notes->where('moyenne', '<', 10)->count(),
                ]
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
